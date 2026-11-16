<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\SchoolMealResponse;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use Throwable;

// The department's response file, one row per school named in our letters. Every column is kept.
// One observation, letter date, response or response date can be a merged cell covering several
// schools' rows. Excel keeps a merged cell's value in its first row only, so each row it covers
// is given that value. Which cells are merged is read from the Excel file itself.
class SchoolMealResponseImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'source_sr' => ['sr no', 'sr', 's no', 'sno', 'serial no'],
        'district' => ['district', 'district name'],
        'emis_code' => ['emis', 'emis code', 'school emis code', 'emis no'],
        'smu_observation' => ['smu observation', 'smu observations', 'observation', 'observations'],
        'letter_date' => ['smu letter data', 'smu letter date', 'letter date'],
        'response' => ['response', 'department response', 'reply'],
        'responded_at' => ['department response date', 'response date', 'reply date'],
    ];

    private const REQUIRED = ['emis_code', 'response'];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->reader->readSheet(
            $path,
            $extension,
            self::MAX_ROWS,
            fn (array $firstRows) => $this->findHeading($firstRows, self::COLUMNS, self::REQUIRED) !== null
        );

        if ($sheet['rows'] === []) {
            throw new RuntimeException('This file has no rows.');
        }

        $header = $this->findHeading($sheet['rows'], self::COLUMNS, self::REQUIRED);

        if ($header === null) {
            throw new RuntimeException($this->missingHeadingsMessage());
        }

        $ranges = $extension === 'xlsx' ? $this->mergedRanges($path, $sheet['sheet']) : [];
        $lines = $this->withMergedCellsFilled($sheet['rows'], $ranges);
        $blocks = $this->observationBlocks($ranges, array_search('smu_observation', $header['columns'], true));

        $rows = [];

        foreach ($lines as $number => $values) {
            if ($number <= $header['row']) {
                continue;
            }

            $row = [];
            foreach ($header['columns'] as $index => $key) {
                $row[$key] = $values[$index] ?? null;
            }

            $emis = $this->emisCode($row['emis_code'] ?? null);
            $observation = $this->cleanText($row['smu_observation'] ?? null);
            $response = $this->cleanText($row['response'] ?? null);

            // A line with no school, observation or response, such as a blank or total line.
            if ($emis === null && $observation === null && $response === null) {
                continue;
            }

            $district = PsrpPhase1Districts::normalize($row['district'] ?? null);

            $rows[] = [
                'source_sr' => is_numeric($row['source_sr'] ?? null) && (float) $row['source_sr'] >= 0 ? (int) $row['source_sr'] : null,
                'district' => $district === null ? null : mb_substr($district, 0, 100),
                'emis_code' => $emis,
                'smu_observation' => $observation,
                'observation_block' => $blocks[$number] ?? null,
                'letter_date' => $this->date($row['letter_date'] ?? null),
                'response' => $response,
                'responded_at' => $this->date($row['responded_at'] ?? null),
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('No schools or responses were found in this file.');
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    public function save(array $prepared, InitiativeFile $file): void
    {
        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            SchoolMealResponse::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // Gives every cell a merged range covers the value in the range's first cell.
    private function withMergedCellsFilled(array $rows, array $ranges): array
    {
        foreach ($ranges as $range) {
            if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $range, $parts) !== 1) {
                continue;
            }

            [$firstColumn, $firstRow] = [$this->columnIndex($parts[1]), (int) $parts[2]];
            [$lastColumn, $lastRow] = [$this->columnIndex($parts[3]), (int) $parts[4]];
            $value = $rows[$firstRow][$firstColumn] ?? null;

            for ($line = $firstRow; $line <= $lastRow; $line++) {
                for ($column = $firstColumn; $column <= $lastColumn; $column++) {
                    if ($line !== $firstRow || $column !== $firstColumn) {
                        $rows[$line][$column] = $value;
                    }
                }
            }
        }

        ksort($rows);

        return $rows;
    }

    // [sheet row => the row its merged observation cell starts on], for the rows one merged
    // observation covers. Those schools share one observation, and the page says so.
    private function observationBlocks(array $ranges, int|false $column): array
    {
        $blocks = [];

        foreach ($ranges as $range) {
            if ($column === false || preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $range, $parts) !== 1) {
                continue;
            }

            [$firstRow, $lastRow] = [(int) $parts[2], (int) $parts[4]];

            if ($firstRow < $lastRow && $this->columnIndex($parts[1]) <= $column && $column <= $this->columnIndex($parts[3])) {
                for ($line = $firstRow; $line <= $lastRow; $line++) {
                    $blocks[$line] = $firstRow;
                }
            }
        }

        return $blocks;
    }

    // The merged ranges ("D5:D7") of the sheet the rows came from. The reader names that sheet only
    // when the file has more than one, otherwise it is the sheet holding rows.
    private function mergedRanges(string $path, ?string $sheetName): array
    {
        $reader = new XlsxReader(new XlsxOptions(SHOULD_LOAD_MERGE_CELLS: true));

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheetName !== null ? $sheet->getName() === $sheetName : $this->hasRows($sheet)) {
                    return $sheet->getMergeCells();
                }
            }
        } catch (Throwable $e) {
            // The rows were already read, so the file is readable; without the merges they stay as written.
            report($e);
        } finally {
            $reader->close();
        }

        return [];
    }

    private function hasRows(object $sheet): bool
    {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->cells as $cell) {
                if ($cell->getValue() !== null && $cell->getValue() !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    // "A" is column 0, "Z" 25, "AA" 26.
    private function columnIndex(string $letters): int
    {
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column and a response column, such as "EMIS" and "Response".';
    }
}
