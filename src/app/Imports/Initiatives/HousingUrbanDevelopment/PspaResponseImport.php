<?php

namespace App\Imports\Initiatives\HousingUrbanDevelopment;

use App\Imports\InitiativeFileImport;
use App\Imports\Initiatives\LocalGovernment\ReadsUcMcValues;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaResponse;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use RuntimeException;

// The department's response file for Punjab Saaf Pani Authority (PSPA), one row per plant in one of our
// reports. Every column is kept as typed. The file has two columns headed "Department Response": each is
// the reply on the question just before it ("Non-functional" or "NO FOUND"). Issues, actions and replies
// keep their line breaks; names, codes and dates lose them. Excel's "#N/A" in CODE is a blank. "SMU
// Report Date" often lists several dates, so it is kept as typed; "Department Response Date" is a date.
class PspaResponseImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;
    use ReadsUcMcValues;

    private const MAX_ROWS = 100000;

    // The two "Department Response" columns are found separately, by the question before each.
    private const COLUMNS = [
        'source_sr' => ['sr no', 'sr', 's no', 'sno', 'serial no'],
        'site_name' => ['site name', 'name of site', 'site'],
        'plant_code' => ['code', 'plant code', 'site code'],
        'district' => ['district', 'district name'],
        'overall_issues' => ['overall issues', 'issues', 'issues identified'],
        'action_taken' => ['action taken'],
        'smu_report_date' => ['smu report date', 'smu report dates', 'report date'],
        'response_date' => ['department response date', 'response date'],
        'non_functional' => ['non functional'],
        'not_found' => ['no found', 'not found'],
    ];

    private const RESPONSE_HEADING = 'department response';

    // [question column => the column for the department's response on it]
    private const RESPONSES = [
        'non_functional' => 'non_functional_response',
        'not_found' => 'not_found_response',
    ];

    // Short values, kept on one line: [column => the file's heading, the longest value the table keeps]
    private const SHORT_TEXT = [
        'site_name' => ['Site Name', 255],
        'plant_code' => ['CODE', 20],
        'district' => ['District', 100],
        'smu_report_date' => ['SMU Report Date', 255],
        'non_functional' => ['Non-functional', 100],
        'not_found' => ['NO FOUND', 100],
    ];

    // Paragraphs, kept with their line breaks.
    private const LONG_TEXT_COLUMNS = [
        'overall_issues' => 'Overall Issues',
        'action_taken' => 'Action Taken',
        'non_functional_response' => 'Department Response (Non-functional)',
        'not_found_response' => 'Department Response (NO FOUND)',
    ];

    // The longest a cell can be in Excel, well within the table's medium text.
    private const LONG_TEXT = 65535;

    // How Excel writes a formula that failed, such as a lookup that found no code.
    private const EXCEL_ERRORS = ['#n/a', '#ref!', '#value!', '#div/0!', '#name?', '#null!', '#num!'];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readResponseSheet($path, $extension);
        $dayFirst = $this->writesDayFirst($sheet['rows']);

        $rows = [];
        $problems = [];

        foreach ($sheet['rows'] as $number => $row) {
            $values = [
                'source_sr' => $this->wholeNumber($row['source_sr'] ?? null, $number, 'Sr. No.', $problems),
            ];

            foreach (self::SHORT_TEXT as $column => [$label, $max]) {
                $values[$column] = $this->fittingText($this->oneLine($row[$column] ?? null), $max, $number, $label, $problems);
            }

            if ($values['plant_code'] !== null && in_array(mb_strtolower($values['plant_code']), self::EXCEL_ERRORS, true)) {
                $values['plant_code'] = null;
            }

            foreach (self::LONG_TEXT_COLUMNS as $column => $label) {
                $values[$column] = $this->fittingText($row[$column] ?? null, self::LONG_TEXT, $number, $label, $problems);
            }

            $values['response_date'] = $this->readDate($row['response_date'] ?? null, $dayFirst, $number, 'Department Response Date', $problems);

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
            PspaResponse::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // The sheet's rows keyed by column, as readHeadedSheet gives them, with each "Department Response"
    // column given to the question before it. Line breaks are kept, for the paragraphs.
    private function readResponseSheet(string $path, string $extension): array
    {
        $required = ['district', 'action_taken'];
        $findHeader = fn (array $rows) => $this->findHeading($rows, self::COLUMNS, $required);

        $sheet = $this->reader->keepingLineBreaks()->readSheet($path, $extension, self::MAX_ROWS, fn (array $firstRows) => $findHeader($firstRows) !== null);

        if ($sheet['rows'] === []) {
            throw new RuntimeException('This file has no rows.');
        }

        $header = $findHeader($sheet['rows']);

        if ($header === null) {
            throw new RuntimeException($this->missingHeadingsMessage());
        }

        $columns = $header['columns'];

        foreach ($sheet['rows'][$header['row']] as $index => $heading) {
            if (! is_string($heading) || $this->normalizeHeading($heading) !== self::RESPONSE_HEADING) {
                continue;
            }

            // The nearest question to the left. Without one, the first response not yet found.
            $question = null;

            foreach ($columns as $at => $key) {
                if ($at < $index && isset(self::RESPONSES[$key]) && ($question === null || $at > $question[0])) {
                    $question = [$at, $key];
                }
            }

            $column = $question !== null ? self::RESPONSES[$question[1]] : null;

            if ($column === null || in_array($column, $columns, true)) {
                $column = collect(self::RESPONSES)->first(fn ($response) => ! in_array($response, $columns, true));
            }

            if ($column !== null) {
                $columns[$index] = $column;
            }
        }

        $rows = [];

        foreach ($sheet['rows'] as $number => $values) {
            if ($number <= $header['row']) {
                continue;
            }

            $row = [];

            foreach ($columns as $index => $key) {
                $row[$key] = $values[$index] ?? null;
            }

            $rows[$number] = $row;
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    // A name, code or date written over several lines ("Toba Tek↵Singh") is kept on one line.
    private function oneLine(mixed $value): mixed
    {
        return is_string($value) ? (preg_replace('/[\s\x{00A0}]+/u', ' ', $value) ?? $value) : $value;
    }

    // Dates typed as text, as in a CSV, can be 17/04/2025 or 4/17/2025. A day above 12 in either place
    // shows which the file uses. If nothing shows it, the day comes first, as in the file's 12.01.2026.
    private function writesDayFirst(array $rows): bool
    {
        $monthFirst = false;

        foreach ($rows as $row) {
            $value = $row['response_date'] ?? null;

            if (is_string($value) && preg_match('#^\s*(\d{1,2})[/.-](\d{1,2})[/.-]\d{4}#', $value, $parts) === 1) {
                if ((int) $parts[1] > 12) {
                    return true;
                }

                $monthFirst = $monthFirst || (int) $parts[2] > 12;
            }
        }

        return ! $monthFirst;
    }

    private function readDate(mixed $value, bool $dayFirst, int $row, string $label, array &$problems): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $text = $this->asTyped($this->oneLine($value));

        // A cell with no letter or digit, such as a lone ".", holds no date.
        if ($text === null || preg_match('/[\p{L}\p{N}]/u', $text) !== 1) {
            return null;
        }

        $date = null;

        if (is_numeric($text) && (float) $text >= 1 && (float) $text < 100000) {
            // An Excel date written as its day number.
            $date = Carbon::create(1899, 12, 30)->addDays((int) floor((float) $text));
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $text, $parts) === 1) {
            $date = checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? Carbon::create((int) $parts[1], (int) $parts[2], (int) $parts[3]) : null;
        } elseif (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $text, $parts) === 1) {
            [$day, $month] = $dayFirst ? [(int) $parts[1], (int) $parts[2]] : [(int) $parts[2], (int) $parts[1]];
            $date = checkdate($month, $day, (int) $parts[3]) ? Carbon::create((int) $parts[3], $month, $day) : null;
        }

        if ($date === null || $date->year < 2000 || $date->year > 2100) {
            $problems[] = "Row {$row}, {$label}: \"".mb_strimwidth($text, 0, 40, '…').'" is not a date. Use a format like 12.01.2026.';

            return null;
        }

        return $date->format('Y-m-d');
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs a "District" column and an "Action Taken" column.';
    }
}
