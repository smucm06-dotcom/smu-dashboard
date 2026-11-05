<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Imports\SpreadsheetReader;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

// Reads a PSRP visit file: finds its headings, checks each row and saves what is new or changed.
// Each phase only says which columns it has and which table they go in.
abstract class PsrpVisitImport
{
    protected const MAX_ROWS = 50000;

    private const MAX_LISTED_PROBLEMS = 500;

    // Written in the latitude and longitude columns when a visit has no location.
    protected const NO_LOCATION_VALUES = ['no coordinates', 'no coordinate', 'not available', 'not found'];

    // The table's unique key: a re-uploaded visit updates its row instead of adding another.
    private const KEY_COLUMNS = ['emis_code', 'visited_at'];

    private const BLANK_VALUES = ['', '-', '--', 'n/a', 'na', 'null', 'nl', 'nil'];

    private const TRUE_VALUES = ['yes', 'y', 'true', '1', 'paid', 'available', 'established', 'formed', 'handed over', 'charged'];

    private const FALSE_VALUES = ['no', 'n', 'false', '0', 'not paid', 'unpaid', 'not available', 'not established', 'not formed', 'not handed over', 'not charged'];

    // Future dates whose day and month were swapped in the file, and were put right.
    private int $correctedDates = 0;

    // Coordinates that could not be used. The visit is still saved, without a location.
    private int $ignoredCoordinates = 0;

    public function __construct(protected SpreadsheetReader $reader)
    {
    }

    // The file's columns: [table column => how it is labelled, checked and may be headed].
    abstract protected function columns(): array;

    // The model of the table the visits are saved in.
    abstract protected function model(): string;

    // For a phase whose file has dates known to be mistyped.
    protected function correctedDate(Carbon $date): Carbon
    {
        return $date;
    }

    // For a phase whose file holds more of a visit's data on other sheets.
    protected function withExtraSheets(array $records, array $columns, string $path, string $extension): array
    {
        return [$records, $columns];
    }

    public function run(string $path, string $extension): array
    {
        $this->correctedDates = 0;
        $this->ignoredCoordinates = 0;

        try {
            $rows = $this->reader->read(
                $path,
                $extension,
                self::MAX_ROWS,
                fn (array $firstRows) => $this->findHeader($firstRows) !== null
            );
        } catch (RuntimeException $e) {
            return ['failure' => $e->getMessage()];
        }

        if ($rows === []) {
            return ['failure' => 'This file has no rows.'];
        }

        $header = $this->findHeader($rows);

        if ($header === null) {
            return [
                'failure' => 'The column headings could not be found. The file needs an EMIS column (such as "EMIS Code") '
                    .'and a visit date column (such as "Timestamp" or "Visit date").',
                'ignoredColumns' => array_values(array_filter(array_map(
                    fn ($heading) => $this->displayValue($heading),
                    reset($rows)
                ))),
            ];
        }

        $dataRows = array_filter($rows, fn ($number) => $number > $header['number'], ARRAY_FILTER_USE_KEY);
        $dateIndex = array_search('visited_at', $header['columns'], true);
        $dateOrder = $this->slashDateOrder(array_column($dataRows, $dateIndex));

        $records = [];
        $rowOfKey = [];
        $problems = [];
        $badRows = 0;

        foreach ($dataRows as $number => $values) {
            $record = [];
            $rowProblems = [];

            foreach ($header['columns'] as $index => $column) {
                $value = $values[$index] ?? null;

                try {
                    $record[$column] = $this->parse($value, $this->columns()[$column], $dateOrder);
                } catch (InvalidArgumentException $e) {
                    $rowProblems[] = $this->problem($number, $column, $value, $e->getMessage());
                }
            }

            if ($rowProblems === []) {
                $key = $record['emis_code'].'|'.$record['visited_at'];

                if (isset($rowOfKey[$key])) {
                    $rowProblems[] = $this->problem(
                        $number,
                        'emis_code',
                        $record['emis_code'],
                        "Same school and visit date as row {$rowOfKey[$key]}."
                    );
                } else {
                    $rowOfKey[$key] = $number;
                    $records[$key] = $record;
                }
            }

            if ($rowProblems !== []) {
                $badRows++;
                array_push($problems, ...$rowProblems);
            }
        }

        // Every row is checked first. If any row has a problem, nothing from this file is saved.
        if ($badRows > 0) {
            return [
                'failure' => ($badRows === 1 ? '1 row in this file has a problem.' : number_format($badRows).' rows in this file have problems.')
                    .' Correct the file and upload it again.',
                'badRows' => $badRows,
                'problemCount' => count($problems),
                'problems' => array_slice($problems, 0, self::MAX_LISTED_PROBLEMS),
            ];
        }

        [$records, $columns] = $this->withExtraSheets($records, array_values($header['columns']), $path, $extension);

        return $this->save($records, $columns) + [
            'datesCorrected' => $this->correctedDates,
            'coordinatesIgnored' => $this->ignoredCoordinates,
            'ignoredColumns' => $header['ignored'],
            'missingColumns' => array_values(array_map(
                fn ($column) => $column['label'],
                array_diff_key($this->columns(), array_flip($columns))
            )),
        ];
    }

    // The heading row may sit below a report title, so check the first few rows.
    protected function findHeader(array $rows): ?array
    {
        $columnOfHeading = $this->columnOfHeading();

        foreach (array_slice($rows, 0, 10, true) as $number => $values) {
            $columns = [];
            $ignored = [];

            foreach ($values as $index => $heading) {
                if (! is_string($heading) || $heading === '') {
                    continue;
                }

                $column = $columnOfHeading[$this->normalizeHeading($heading)] ?? null;

                if ($column === null || in_array($column, $columns, true)) {
                    $ignored[] = $heading;
                } else {
                    $columns[$index] = $column;
                }
            }

            if (array_diff(self::KEY_COLUMNS, $columns) === []) {
                return compact('number', 'columns', 'ignored');
            }
        }

        return null;
    }

    private function columnOfHeading(): array
    {
        $map = [];

        foreach ($this->columns() as $column => $definition) {
            foreach ([$column, $definition['label'], ...$definition['headings']] as $heading) {
                $map[$this->normalizeHeading($heading)] = $column;
            }
        }

        return $map;
    }

    private function normalizeHeading(string $heading): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', strtolower($heading)));
    }

    // 03/04/2025 could be March or April, so the file's other dates decide which comes
    // first. If they cannot, those rows are reported instead of guessing.
    private function slashDateOrder(array $values): ?string
    {
        $dayFirst = false;
        $monthFirst = false;

        foreach ($values as $value) {
            if (is_string($value) && preg_match('#^(\d{1,2})[/-](\d{1,2})[/-]\d{4}#', $value, $parts) === 1) {
                $dayFirst = $dayFirst || (int) $parts[1] > 12;
                $monthFirst = $monthFirst || (int) $parts[2] > 12;
            }
        }

        return match (true) {
            $dayFirst && ! $monthFirst => 'day',
            $monthFirst && ! $dayFirst => 'month',
            default => null,
        };
    }

    protected function parse(mixed $value, array $column, ?string $dateOrder): ?string
    {
        if ($this->isBlank($value, $column)) {
            if (! empty($column['required'])) {
                throw new InvalidArgumentException('This value is required.');
            }

            return null;
        }

        return match ($column['type']) {
            'integer' => $this->integer($value),
            'boolean' => $this->boolean($value),
            'decimal' => $this->decimal($value, $column['limit']),
            'datetime' => $this->dateTime($value, $dateOrder),
            'date' => substr($this->dateTime($value, $dateOrder), 0, 10),
            'code' => $this->code($value, $column['max']),
            'title' => Str::title($this->text($value, $column['max'])),
            'district' => PsrpPhase1Districts::normalize($this->text($value, $column['max'])),
            default => $this->text($value, $column['max']),
        };
    }

    private function isBlank(mixed $value, array $column): bool
    {
        return $value === null
            || (is_string($value) && in_array(
                mb_strtolower($value),
                [...self::BLANK_VALUES, ...($column['blanks'] ?? [])],
                true
            ));
    }

    private function integer(mixed $value): string
    {
        $number = is_string($value) ? str_replace(',', '', $value) : $value;

        if (
            ! is_int($number) && ! is_float($number) && ! (is_string($number) && is_numeric($number))
            || (float) $number < 0
            || (float) $number > 4294967295
            || floor((float) $number) !== (float) $number
        ) {
            throw new InvalidArgumentException('Must be a whole number, 0 or more.');
        }

        return (string) (int) $number;
    }

    private function boolean(mixed $value): string
    {
        $answer = is_bool($value) ? ($value ? 'true' : 'false') : mb_strtolower($this->displayValue($value));

        return match (true) {
            in_array($answer, self::TRUE_VALUES, true) => '1',
            in_array($answer, self::FALSE_VALUES, true) => '0',
            default => throw new InvalidArgumentException('Must be Yes or No.'),
        };
    }

    // A bad coordinate should not lose the whole visit, so it is left blank and counted.
    private function decimal(mixed $value, int $limit): ?string
    {
        if ((! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric($value))) || abs((float) $value) > $limit) {
            $this->ignoredCoordinates++;

            return null;
        }

        return number_format((float) $value, 7, '.', '');
    }

    private function dateTime(mixed $value, ?string $dateOrder): string
    {
        $date = match (true) {
            $value instanceof DateTimeInterface => Carbon::instance($value),
            is_int($value), is_float($value), is_string($value) && is_numeric($value) => $this->dateFromExcelNumber((float) $value),
            is_string($value) => $this->dateFromText($value, $dateOrder),
            default => null,
        };

        if ($date === null) {
            throw new InvalidArgumentException('Not a valid date. Use a format like 2025-03-27 14:30:00.');
        }

        $date = $this->correctedDate($this->withSwappedFutureDate($date));

        if ($date->year < 2000 || $date->greaterThan(now()->addDay())) {
            throw new InvalidArgumentException('This date is outside the expected range, from 2000 to today.');
        }

        // Whole seconds, so the same visit matches whether it comes from CSV or Excel.
        return $date->format('Y-m-d H:i:s');
    }

    // A visit cannot be in the future, so if swapping the day and month gives a past date, that is the real one.
    private function withSwappedFutureDate(Carbon $date): Carbon
    {
        if ($date->lessThanOrEqualTo(now()->addDay()) || $date->day > 12) {
            return $date;
        }

        $swapped = $date->copy()->setDate($date->year, $date->day, $date->month);

        if ($swapped->greaterThan(now()->addDay())) {
            return $date;
        }

        $this->correctedDates++;

        return $swapped;
    }

    private function dateFromExcelNumber(float $days): ?Carbon
    {
        return $days >= 1 && $days < 100000
            ? Carbon::create(1899, 12, 30)->addSeconds((int) round($days * 86400))
            : null;
    }

    private function dateFromText(string $value, ?string $dateOrder): ?Carbon
    {
        $time = '(?:[ T](\d{1,2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?\s*([AaPp][Mm])?)?';

        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})'.$time.'$#', $value, $parts) === 1) {
            return $this->buildDate((int) $parts[1], (int) $parts[2], (int) $parts[3], array_slice($parts, 4));
        }

        // Dates written with dots are day first. If that is impossible, the day and month were swapped.
        if (preg_match('#^(\d{1,2})\.(\d{1,2})\.(\d{4})'.$time.'$#', $value, $parts) === 1) {
            [$day, $month] = [(int) $parts[1], (int) $parts[2]];

            if ($month > 12 && $day <= 12) {
                [$day, $month] = [$month, $day];
                $this->correctedDates++;
            }

            return $this->buildDate((int) $parts[3], $month, $day, array_slice($parts, 4));
        }

        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})'.$time.'$#', $value, $parts) !== 1) {
            return null;
        }

        if ($dateOrder === null) {
            throw new InvalidArgumentException('Day and month cannot be told apart in this file. Use a format like 2025-03-27.');
        }

        [$day, $month] = $dateOrder === 'day' ? [$parts[1], $parts[2]] : [$parts[2], $parts[1]];

        return $this->buildDate((int) $parts[3], (int) $month, (int) $day, array_slice($parts, 4));
    }

    private function buildDate(int $year, int $month, int $day, array $time): ?Carbon
    {
        $hour = (int) ($time[0] ?? 0);
        $minute = (int) ($time[1] ?? 0);
        $second = (int) ($time[2] ?? 0);
        $meridiem = strtolower($time[3] ?? '');

        if ($meridiem !== '') {
            if ($hour < 1 || $hour > 12) {
                return null;
            }

            $hour = $hour % 12 + ($meridiem === 'pm' ? 12 : 0);
        }

        if (! checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        return Carbon::create($year, $month, $day, $hour, $minute, $second);
    }

    private function code(mixed $value, int $max): string
    {
        $code = is_float($value) && floor($value) === $value ? (string) (int) $value : $this->displayValue($value);

        if (preg_match('/^\d+(\.\d+)?[eE]\+?\d+$/D', $code) === 1) {
            throw new InvalidArgumentException('Excel has shortened this code. Format the column as Text or Number and save again.');
        }

        if (preg_match('/^[0-9A-Za-z-]+$/D', $code) !== 1) {
            throw new InvalidArgumentException('Must contain only letters and numbers.');
        }

        if (strlen($code) > $max) {
            throw new InvalidArgumentException("Must be {$max} characters or fewer.");
        }

        return $code;
    }

    private function text(mixed $value, int $max): string
    {
        $text = $this->displayValue($value);

        if (mb_strlen($text) > $max) {
            throw new InvalidArgumentException("Must be {$max} characters or fewer.");
        }

        return $text;
    }

    private function displayValue(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'TRUE' : 'FALSE',
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof DateInterval => $value->format('%H:%I:%S'),
            default => (string) $value,
        };
    }

    private function problem(int $row, string $column, mixed $value, string $message): array
    {
        return [
            'row' => $row,
            'column' => $this->columns()[$column]['label'],
            'value' => Str::limit($this->displayValue($value), 60),
            'message' => $message,
        ];
    }

    // Adds new visits and updates changed ones. Rows not in the file are left alone.
    private function save(array $records, array $columns): array
    {
        $existing = $this->existingRecords(array_values($records), $columns);
        $changes = [];
        $added = 0;
        $updated = 0;

        foreach ($records as $key => $record) {
            if (! isset($existing[$key])) {
                $added++;
                $changes[] = $record;
            } elseif ($existing[$key] !== $record) {
                $updated++;
                $changes[] = $record;
            }
        }

        DB::transaction(function () use ($changes, $columns) {
            foreach (array_chunk($changes, 500) as $chunk) {
                $this->model()::upsert($chunk, self::KEY_COLUMNS, array_values(array_diff($columns, self::KEY_COLUMNS)));
            }
        });

        return [
            'added' => $added,
            'updated' => $updated,
            'unchanged' => count($records) - $added - $updated,
        ];
    }

    // Saved rows in the same shape as parsed rows, so the two can be compared directly.
    private function existingRecords(array $records, array $columns): array
    {
        $existing = [];

        foreach (array_chunk(array_unique(array_column($records, 'emis_code')), 1000) as $emisCodes) {
            $rows = $this->model()::query()->toBase()->whereIn('emis_code', $emisCodes)->get($columns);

            foreach ($rows as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $value = $row->{$column};

                    $values[$column] = match (true) {
                        $value === null => null,
                        $column === 'visited_at' => substr($value, 0, 19),
                        default => (string) $value,
                    };
                }

                $existing[$values['emis_code'].'|'.$values['visited_at']] = $values;
            }
        }

        return $existing;
    }
}
