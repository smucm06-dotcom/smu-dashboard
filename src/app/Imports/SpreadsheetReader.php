<?php

namespace App\Imports;

use Generator;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\ErrorCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\SheetWithVisibilityInterface;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use Throwable;

class SpreadsheetReader
{
    private const PREVIEW_ROWS = 10;

    // Returns the data sheet's non-empty rows, keyed by their row number in the file.
    // $isWanted receives a sheet's first rows, for callers that know what their data looks like.
    public function read(string $path, string $extension, int $maxRows, ?callable $isWanted = null): array
    {
        return $this->readSheet($path, $extension, $maxRows, $isWanted)['rows'];
    }

    // The same rows, plus the sheet's name when there were sheets to choose from.
    public function readSheet(string $path, string $extension, int $maxRows, ?callable $isWanted = null): array
    {
        $sheets = $this->sheets($path, $extension);
        $sheet = $this->wantedSheet($sheets, $isWanted);

        if ($sheet === null) {
            return ['rows' => [], 'sheet' => null];
        }

        $rows = [];

        foreach ($this->rows($path, $extension, $sheet['index']) as $row) {
            if (count($rows) === $maxRows) {
                throw new RuntimeException('This file has more than '.number_format($maxRows).' rows. Split it into smaller files.');
            }

            $rows[$row['number']] = $row['values'];
        }

        return ['rows' => $rows, 'sheet' => count($sheets) > 1 ? $sheet['name'] : null];
    }

    // Counts the entries in the data sheet, leaving out title rows, the heading and a total row.
    // Returns null for a file with no rows. "sheet" is only set when there were sheets to choose from.
    public function countEntries(string $path, string $extension): ?array
    {
        $sheets = $this->sheets($path, $extension);
        $sheet = $this->wantedSheet($sheets);

        if ($sheet === null) {
            return null;
        }

        $count = $sheet['count'] - $this->headingPosition($sheet['preview']) - 1;

        if ($count > 0 && $this->isTotalRow($sheet['last'])) {
            $count--;
        }

        return [
            'count' => max(0, $count),
            'sheet' => count($sheets) > 1 ? $sheet['name'] : null,
        ];
    }

    // One pass over the file, keeping only each sheet's size and first rows.
    // Visible sheets come first, then the largest, so a summary sheet is not mistaken for the data.
    private function sheets(string $path, string $extension): array
    {
        $sheets = [];

        foreach ($this->rows($path, $extension) as $row) {
            $sheet = $row['sheet'];
            $index = $sheet->getIndex();

            $sheets[$index] ??= [
                'index' => $index,
                'name' => $sheet->getName(),
                'visible' => ! $sheet instanceof SheetWithVisibilityInterface || $sheet->isVisible(),
                'count' => 0,
                'preview' => [],
                'last' => [],
            ];

            $sheets[$index]['count']++;
            $sheets[$index]['last'] = $row['values'];

            if (count($sheets[$index]['preview']) < self::PREVIEW_ROWS) {
                $sheets[$index]['preview'][$row['number']] = $row['values'];
            }
        }

        usort($sheets, fn ($a, $b) => [$b['visible'], $b['count']] <=> [$a['visible'], $a['count']]);

        return $sheets;
    }

    private function wantedSheet(array $sheets, ?callable $isWanted = null): ?array
    {
        foreach ($sheets as $sheet) {
            if ($isWanted === null || $isWanted($sheet['preview'])) {
                return $sheet;
            }
        }

        return $sheets[0] ?? null;
    }

    // Title lines above a table fill only a cell or two, so the heading is the first
    // row that is more than half as wide as the widest of the first rows.
    private function headingPosition(array $preview): int
    {
        $widths = array_map(
            fn ($values) => count(array_filter($values, fn ($value) => $value !== null && $value !== '')),
            array_values($preview)
        );

        foreach ($widths as $position => $width) {
            if ($width * 2 > max($widths)) {
                return $position;
            }
        }

        return 0;
    }

    private function isTotalRow(array $values): bool
    {
        foreach ($values as $value) {
            if (is_string($value) && preg_match('/^(grand\s+)?total$/i', $value) === 1) {
                return true;
            }
        }

        return false;
    }

    private function rows(string $path, string $extension, ?int $onlySheet = null): Generator
    {
        // Empty rows are kept while reading so row numbers match what the user sees.
        $reader = $extension === 'xlsx'
            ? new XlsxReader(new XlsxOptions(SHOULD_PRESERVE_EMPTY_ROWS: true))
            : new CsvReader(new CsvOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                if ($onlySheet !== null && $sheet->getIndex() !== $onlySheet) {
                    continue;
                }

                foreach ($sheet->getRowIterator() as $number => $row) {
                    $values = array_map($this->cellValue(...), $row->cells);

                    if (array_filter($values, fn ($value) => $value !== null && $value !== '') !== []) {
                        yield ['sheet' => $sheet, 'number' => $number, 'values' => $values];
                    }
                }

                if ($onlySheet !== null) {
                    break;
                }
            }
        } catch (Throwable $e) {
            throw new RuntimeException(
                'This file could not be read. Check that it is a valid CSV or Excel (.xlsx) file and try again.',
                previous: $e
            );
        } finally {
            $reader->close();
        }
    }

    private function cellValue(Cell $cell): mixed
    {
        $value = match (true) {
            $cell instanceof FormulaCell => $cell->getComputedValue(),
            $cell instanceof ErrorCell => $cell->getRawValue(),
            default => $cell->getValue(),
        };

        if (! is_string($value)) {
            return is_array($value) ? null : $value;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $value));
    }
}
