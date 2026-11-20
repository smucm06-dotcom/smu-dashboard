<?php

namespace App\Imports\Initiatives\LocalGovernment;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\LocalGovernment\PdpResponse;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use RuntimeException;

// The department's response file for the Chief Minister Punjab Development Program (PDP), one row per SMU
// report: its dates, the report's text and the department's answer. Every column is kept as typed, and the
// two texts keep their line breaks, as the page reads them city by city. The latest file replaces the
// earlier one (InitiativeFileController).
class PdpResponseImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;
    use ReadsUcMcValues;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'response_letter_date' => ['response letter date', 'letter date', 'response date', 'department letter date'],
        'smu_report_date' => ['smu report date', 'report date'],
        'smu_report' => ['smu report', 'smu report text', 'smu observations', 'issues identified', 'issues'],
        'dept_response' => ['dept response', 'department response', 'department s response', 'response', 'action taken'],
    ];

    private const DATES = [
        'response_letter_date' => 'Response Letter Date',
        'smu_report_date' => 'SMU Report Date',
    ];

    private const TEXTS = [
        'smu_report' => 'SMU Report',
        'dept_response' => 'Dept. Response',
    ];

    // The longest a cell can be in Excel, well within the table's medium text.
    private const LONG_TEXT = 65535;

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readHeadedSheet($this->reader->keepingLineBreaks(), $path, $extension, self::COLUMNS, ['smu_report_date', 'dept_response'], self::MAX_ROWS);
        $dayFirst = $this->writesDayFirst($sheet['rows']);

        $rows = [];
        $problems = [];

        foreach ($sheet['rows'] as $number => $row) {
            $values = [];

            foreach (self::DATES as $column => $label) {
                $values[$column] = $this->readDate($row[$column] ?? null, $dayFirst, $number, $label, $problems);
            }

            foreach (self::TEXTS as $column => $label) {
                $values[$column] = $this->fittingText($row[$column] ?? null, self::LONG_TEXT, $number, $label, $problems);
            }

            // A blank line holds nothing to keep.
            if (array_filter($values, fn ($value) => $value !== null) === []) {
                continue;
            }

            $rows[] = $values;
        }

        $this->refuseIfProblems($problems);

        if ($rows === []) {
            throw new RuntimeException('No responses were found in this file.');
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    public function save(array $prepared, InitiativeFile $file): void
    {
        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            PdpResponse::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // Dates typed as text, as in a CSV, can be 17/04/2026 or 4/17/2026. A day above 12 in either place
    // shows which the file uses. If nothing shows it, the day comes first, as on the other pages.
    private function writesDayFirst(array $rows): bool
    {
        $monthFirst = false;

        foreach ($rows as $row) {
            foreach (array_keys(self::DATES) as $column) {
                $value = $row[$column] ?? null;

                if (is_string($value) && preg_match('#^\s*(\d{1,2})[/.-](\d{1,2})[/.-]\d{4}#', $value, $parts) === 1) {
                    if ((int) $parts[1] > 12) {
                        return true;
                    }

                    $monthFirst = $monthFirst || (int) $parts[2] > 12;
                }
            }
        }

        return ! $monthFirst;
    }

    private function readDate(mixed $value, bool $dayFirst, int $row, string $label, array &$problems): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $text = $this->asTyped($value);

        if ($text === null) {
            return null;
        }

        $date = null;

        if (is_numeric($text) && (float) $text >= 1 && (float) $text < 100000) {
            // An Excel date written as its day number.
            $date = Carbon::create(1899, 12, 30)->addDays((int) floor((float) $text));
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $text, $parts) === 1) {
            $date = checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? Carbon::create((int) $parts[1], (int) $parts[2], (int) $parts[3]) : null;
        } elseif (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})#', $text, $parts) === 1) {
            [$day, $month] = $dayFirst ? [(int) $parts[1], (int) $parts[2]] : [(int) $parts[2], (int) $parts[1]];
            $date = checkdate($month, $day, (int) $parts[3]) ? Carbon::create((int) $parts[3], $month, $day) : null;
        }

        if ($date === null || $date->year < 2000 || $date->year > 2100) {
            $problems[] = "Row {$row}, {$label}: \"".mb_strimwidth($text, 0, 40, '…').'" is not a date. Use a format like 2026-04-27.';

            return null;
        }

        return $date->format('Y-m-d');
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an "SMU Report Date" column and a "Dept. Response" column.';
    }
}
