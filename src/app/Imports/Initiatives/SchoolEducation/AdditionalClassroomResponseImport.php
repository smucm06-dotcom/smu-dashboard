<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\AdditionalClassroomResponse;
use RuntimeException;

// The department's response file, one row per remark in our report. Every column is kept.
// A report's Sr.No., district, EMIS code and SMU visit date sit in merged cells on its first
// row, and its response date is written once, so they fill each of the report's rows. A new
// report starts on any row with its own Sr.No., district or EMIS code.
class AdditionalClassroomResponseImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'source_sr' => ['sr no', 'sr', 's no', 'sno', 'serial no'],
        'district' => ['district', 'district name'],
        'emis_code' => ['emis code', 'emis', 'school emis code', 'emis no'],
        'visit_date' => ['smu visit date', 'visit date'],
        'sent_remarks' => ['analysis report remark', 'analysis report remarks', 'report remarks', 'remarks', 'remark'],
        'responded_at' => ['department response date', 'response date', 'reply date'],
        'response' => ['department response', 'response', 'reply'],
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['emis_code', 'response'], self::MAX_ROWS);

        $known = $this->fullDates($sheet['rows']);
        $report = null;
        $first = 0;
        $rows = [];

        foreach ($sheet['rows'] as $line => $row) {
            $sr = is_numeric($row['source_sr'] ?? null) && (float) $row['source_sr'] >= 0 ? (int) $row['source_sr'] : null;
            $emis = $this->emisCode($row['emis_code'] ?? null);
            $district = PsrpPhase1Districts::normalize($row['district'] ?? null);

            if ($sr !== null || $emis !== null || $district !== null) {
                $first = count($rows);
                $report = [
                    'source_sr' => $sr,
                    'district' => $district === null ? null : mb_substr($district, 0, 100),
                    'emis_code' => $emis,
                    'visit_date' => null,
                    'responded_at' => null,
                ];
            }

            // Rows above the first report, such as the sheet's title, have nothing to belong to.
            if ($report === null) {
                continue;
            }

            // A date may be written lower down than the report's first row. It fills the rows
            // above it that have none, and the rows below.
            foreach (['visit_date', 'responded_at'] as $key) {
                $date = $this->dateOrShortYear($row[$key] ?? null, $line, $known);

                if ($date !== null) {
                    $report[$key] = $date;

                    for ($i = $first; $i < count($rows); $i++) {
                        $rows[$i][$key] ??= $date;
                    }
                }
            }

            $remark = $this->cleanText($row['sent_remarks'] ?? null);
            $response = $this->cleanText($row['response'] ?? null);

            // An empty line inside a report. A remark with no response is still kept.
            if ($remark === null && $response === null) {
                continue;
            }

            $rows[] = $report + ['sent_remarks' => $remark, 'response' => $response];
        }

        if ($rows === []) {
            throw new RuntimeException('No remarks or responses were found in this file.');
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    public function save(array $prepared, InitiativeFile $file): void
    {
        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            AdditionalClassroomResponse::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // Dates written in full, by row, for filling in a year typed with a digit missing.
    private function fullDates(array $rows): array
    {
        $known = [];

        foreach ($rows as $line => $row) {
            foreach (['visit_date', 'responded_at'] as $key) {
                if (($date = $this->date($row[$key] ?? null)) !== null) {
                    $known[$line][] = $date;
                }
            }
        }

        return $known;
    }

    // A date, or one whose year was typed with a digit missing ("21-10-206" for 21 Oct 2026).
    // The year is taken from the nearest full date above or below that it is a shortened form
    // of, when the result is within a month of that date. Anything else unreadable stays blank.
    private function dateOrShortYear(mixed $value, int|string $line, array $known): ?string
    {
        $date = $this->date($value);

        if ($date !== null || ! is_string($value) || preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{3})$#', trim($value), $parts) !== 1) {
            return $date;
        }

        $lines = array_keys($known);
        $before = array_values(array_filter($lines, fn ($other) => $other <= $line));
        $after = array_values(array_filter($lines, fn ($other) => $other > $line));
        $neighbours = array_merge(end($before) === false ? [] : $known[end($before)], $after === [] ? [] : $known[$after[0]]);

        $best = null;

        foreach ($neighbours as $neighbour) {
            $year = substr($neighbour, 0, 4);

            if (! $this->isShortenedYear($parts[3], $year) || ! checkdate((int) $parts[2], (int) $parts[1], (int) $year)) {
                continue;
            }

            $candidate = sprintf('%04d-%02d-%02d', $year, $parts[2], $parts[1]);
            $gap = abs(strtotime($candidate) - strtotime($neighbour)) / 86400;

            if ($gap <= 31 && ($best === null || $gap < $best[1])) {
                $best = [$candidate, $gap];
            }
        }

        return $best[0] ?? null;
    }

    // Whether dropping one digit of the year gives what was typed: 2026 can be 226, 206 or 202.
    private function isShortenedYear(string $typed, string $year): bool
    {
        for ($i = 0; $i < strlen($year); $i++) {
            if (substr($year, 0, $i).substr($year, $i + 1) === $typed) {
                return true;
            }
        }

        return false;
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column and a response column, such as "EMIS Code" and "Department Response".';
    }
}
