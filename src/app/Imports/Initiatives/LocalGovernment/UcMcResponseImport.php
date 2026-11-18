<?php

namespace App\Imports\Initiatives\LocalGovernment;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\LocalGovernment\UcMcResponse;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use RuntimeException;

// The department's response file for UC MC Office Service Delivery, one row per district in a
// report or letter. Every column is kept as typed. Older rows give the department's letter date,
// newer ones our report's date and the issues it raised. The file's hidden "Lists" sheet only feeds
// its dropdowns, and the sheet with the responses is the one read.
class UcMcResponseImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;
    use ReadsUcMcValues;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'source_sr' => ['sr no', 'sr', 's no', 'sno', 'serial no'],
        'smu_report_date' => ['smu report date', 'report date'],
        'letter_date' => ['letter date', 'department letter date'],
        'division' => ['division', 'division name'],
        'district' => ['district', 'district name'],
        'issues_identified' => ['issues identified', 'issues', 'smu observation', 'smu observations'],
        'cases_reported' => ['cases reported'],
        'cases_admitted' => ['cases admitted'],
        'cases_denied' => ['cases denied'],
        'cases_justified' => ['cases justified'],
        'cases_unaddressed' => ['cases unaddressed', 'cases not addressed'],
        'action_taken' => ['action taken', 'department response', 'response'],
    ];

    private const DATES = [
        'smu_report_date' => 'SMU Report Date',
        'letter_date' => 'Letter Date',
    ];

    private const COUNTS = [
        'cases_reported' => 'Cases Reported',
        'cases_admitted' => 'Cases Admitted',
        'cases_denied' => 'Cases Denied',
        'cases_justified' => 'Cases Justified',
        'cases_unaddressed' => 'Cases Unaddressed',
    ];

    // The longest a cell can be in Excel, well within the table's medium text.
    private const LONG_TEXT = 65535;

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        // The issues and the department's answers are read paragraph by paragraph, so their line breaks are kept.
        $sheet = $this->readHeadedSheet($this->reader->keepingLineBreaks(), $path, $extension, self::COLUMNS, ['district', 'action_taken'], self::MAX_ROWS);
        $dayFirst = $this->writesDayFirst($sheet['rows']);

        $rows = [];
        $problems = [];

        foreach ($sheet['rows'] as $number => $row) {
            $values = [
                'source_sr' => $this->wholeNumber($row['source_sr'] ?? null, $number, 'Sr No', $problems),
            ];

            foreach (self::DATES as $column => $label) {
                $values[$column] = $this->readDate($row[$column] ?? null, $dayFirst, $number, $label, $problems);
            }

            $values['division'] = $this->fittingText($row['division'] ?? null, 100, $number, 'Division', $problems);
            $values['district'] = $this->fittingText($row['district'] ?? null, 100, $number, 'District', $problems);
            $values['issues_identified'] = $this->fittingText($row['issues_identified'] ?? null, self::LONG_TEXT, $number, 'Issues Identified', $problems);

            foreach (self::COUNTS as $column => $label) {
                $values[$column] = $this->wholeNumber($row[$column] ?? null, $number, $label, $problems);
            }

            $values['action_taken'] = $this->fittingText($row['action_taken'] ?? null, self::LONG_TEXT, $number, 'Action Taken', $problems);

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
            UcMcResponse::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // Dates typed as text, as in a CSV, can be 17/04/2025 or 4/17/2025. A day above 12 in either place
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
            $problems[] = "Row {$row}, {$label}: \"".mb_strimwidth($text, 0, 40, '…').'" is not a date. Use a format like 2025-04-17.';

            return null;
        }

        return $date->format('Y-m-d');
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs a "District" column and an "Action Taken" column.';
    }
}
