<?php

namespace App\Imports\CallCenter;

use App\Models\CallCenterUpload;
use App\Models\InitiativeFile;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

// What every call center upload does the same way: find the sheet and its headings, read the rows one at a
// time, check each one, and save them in chunks. It runs inside the background job's transaction, so when any
// row has a problem the job undoes everything and the problems are listed, as on the other pages.
abstract class RowImport
{
    // The problems listed for a failed upload; the rest are counted.
    protected const PROBLEMS_SHOWN = 200;

    // Rows saved per insert.
    protected const CHUNK = 500;

    // Cells left blank, or written "Null", hold nothing.
    protected const BLANKS = ['', 'null'];

    public function __construct(protected SheetStream $stream)
    {
    }

    // The table the rows go into.
    abstract protected function table(): string;

    // Each column: the headings it is read from, its type (text, int, date), the longest text it holds and
    // whether the file must have it. The first heading is the one shown in messages.
    abstract protected function columns(): array;

    // One saved row's extra values, worked out from the row (a hash, say).
    protected function extra(array $row): array
    {
        return [];
    }

    // A row that is not data (a total row) is skipped.
    protected function isData(array $row): bool
    {
        return true;
    }

    // Reads, checks and saves the file's rows for $file. Returns the counts for the result box.
    public function import(string $path, string $extension, InitiativeFile $file, UploadProgress $progress): array
    {
        $progress->phase(CallCenterUpload::PHASE_READING);

        $sheet = $this->dataSheet($path, $extension);
        [$headingRow, $map, $ignored, $missing] = $this->headings($sheet);

        $total = $this->stream->rowCount($path, $extension, $sheet['index']);
        $progress->phase(CallCenterUpload::PHASE_SAVING, $total === null ? null : max(0, $total - $headingRow));

        $columns = $this->columns();
        $problems = [];
        $problemCount = 0;
        $seen = [];
        $buffer = [];
        $saved = 0;
        $read = 0;
        $now = now();

        foreach ($this->stream->rows($path, $extension, $sheet['index']) as $number => $values) {
            if ($number <= $headingRow) {
                continue;
            }

            $read++;
            $row = [];
            $rowProblems = [];

            foreach ($map as $index => $column) {
                [$value, $problem] = $this->clean($values[$index] ?? null, $columns[$column]);
                $row[$column] = $value;

                if ($problem !== null) {
                    $rowProblems[] = ['row' => $number, 'column' => $columns[$column]['headings'][0], 'value' => $this->shown($values[$index] ?? null), 'message' => $problem];
                }
            }

            foreach ($missing as $column) {
                $row[$column] = null;
            }

            if (! $this->isData($row)) {
                continue;
            }

            // Every row is numbered, and a number is used once, so a row can always be found again.
            $key = $this->keyColumn();
            if ($row[$key] === null && ! $this->hasProblemIn($rowProblems, $key)) {
                $rowProblems[] = ['row' => $number, 'column' => $columns[$key]['headings'][0], 'value' => '', 'message' => 'Every row needs a '.$columns[$key]['headings'][0].'.'];
            } elseif ($row[$key] !== null) {
                if (isset($seen[$row[$key]])) {
                    $rowProblems[] = ['row' => $number, 'column' => $columns[$key]['headings'][0], 'value' => (string) $row[$key], 'message' => 'The same '.$columns[$key]['headings'][0].' is on row '.number_format($seen[$row[$key]]).'.'];
                } else {
                    $seen[$row[$key]] = $number;
                }
            }

            if ($rowProblems !== []) {
                $problemCount += count($rowProblems);
                $buffer = [];

                foreach ($rowProblems as $problem) {
                    if (count($problems) < self::PROBLEMS_SHOWN) {
                        $problems[] = $problem;
                    }
                }
            } elseif ($problemCount === 0) {
                // Once one row has a problem nothing more is saved, but the rest is still checked.
                $buffer[] = $row + $this->extra($row) + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now];

                if (count($buffer) === self::CHUNK) {
                    DB::table($this->table())->insert($buffer);
                    $saved += count($buffer);
                    $buffer = [];
                }
            }

            $progress->advance($read);
        }

        if ($problemCount > 0) {
            throw new ImportFailed(
                number_format($problemCount).' '.($problemCount === 1 ? 'problem was' : 'problems were').' found in the file. Fix '.($problemCount === 1 ? 'it' : 'them').' and upload the file again.',
                $problems,
                $problemCount
            );
        }

        if ($buffer !== []) {
            DB::table($this->table())->insert($buffer);
            $saved += count($buffer);
        }

        if ($saved === 0) {
            throw new ImportFailed('This file has no rows under its headings.');
        }

        $progress->advance($read, true);

        return [
            'added' => $saved,
            'sheet' => $sheet['name'],
            'ignoredColumns' => $ignored,
            'missingColumns' => array_map(fn ($column) => $columns[$column]['headings'][0], $missing),
        ];
    }

    // The column every row is numbered by (Sr. No).
    abstract protected function keyColumn(): string;

    // The first sheet whose headings include every column the file must have.
    protected function dataSheet(string $path, string $extension): array
    {
        $sheets = $this->stream->sheets($path, $extension);

        usort($sheets, fn ($a, $b) => [$b['visible'], -$b['index']] <=> [$a['visible'], -$a['index']]);

        foreach ($sheets as $sheet) {
            if ($this->headingRow($sheet['preview'], $this->requiredColumns()) !== null) {
                return $sheet;
            }
        }

        $first = $sheets[0]['preview'] ?? [];
        $headings = array_values(array_filter(array_map(fn ($value) => is_string($value) ? $value : null, reset($first) ?: [])));

        throw new ImportFailed(
            'No sheet in this file has the headings this upload needs: '.implode(', ', array_map(fn ($column) => $this->columns()[$column]['headings'][0], $this->requiredColumns())).'.',
            headingsFound: $headings
        );
    }

    protected function requiredColumns(): array
    {
        return array_keys(array_filter($this->columns(), fn ($column) => $column['required'] ?? false));
    }

    // The row number of the heading row: the first of the sheet's first rows that has all $columns.
    protected function headingRow(array $preview, array $columns): ?int
    {
        foreach ($preview as $number => $values) {
            $found = $this->matchHeadings($values);

            if (array_diff($columns, $found) === []) {
                return $number;
            }
        }

        return null;
    }

    // Each heading's column, by its position in the row.
    protected function matchHeadings(array $values): array
    {
        $lookup = [];

        foreach ($this->columns() as $column => $settings) {
            foreach ($settings['headings'] as $heading) {
                $lookup[self::normal($heading)] = $column;
            }
        }

        $found = [];

        foreach ($values as $index => $value) {
            $key = is_string($value) ? self::normal($value) : '';

            if ($key !== '' && isset($lookup[$key]) && ! in_array($lookup[$key], $found, true)) {
                $found[$index] = $lookup[$key];
            }
        }

        return $found;
    }

    private function headings(array $sheet): array
    {
        $number = $this->headingRow($sheet['preview'], $this->requiredColumns());
        $values = $sheet['preview'][$number];
        $map = $this->matchHeadings($values);

        $ignored = [];
        foreach ($values as $index => $value) {
            if (! isset($map[$index]) && $value !== null && $value !== '') {
                $ignored[] = (string) $value;
            }
        }

        $missing = array_values(array_diff(array_keys($this->columns()), $map));

        return [$number, $map, $ignored, $missing];
    }

    // The cell's value for its column, and the problem with it if it cannot be saved.
    protected function clean(mixed $value, array $column): array
    {
        if (is_string($value) && in_array(strtolower($value), self::BLANKS, true)) {
            $value = null;
        }

        if ($value === null) {
            return [null, null];
        }

        return match ($column['type']) {
            'int' => $this->whole($value),
            'date' => $this->date($value),
            default => $this->text($value, $column['max'] ?? 255),
        };
    }

    private function text(mixed $value, int $max): array
    {
        $text = match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_bool($value) => $value ? 'TRUE' : 'FALSE',
            // Numbers typed into a text column (a phone number, say) keep every digit.
            is_float($value) && floor($value) === $value && abs($value) < 1e15 => sprintf('%.0f', $value),
            default => (string) $value,
        };

        return mb_strlen($text) > $max
            ? [null, 'Longer than '.number_format($max).' characters.']
            : [$text, null];
    }

    private function whole(mixed $value): array
    {
        if (is_int($value) && $value >= 0) {
            return [$value, null];
        }

        if (is_float($value) && $value >= 0 && floor($value) === $value) {
            return [(int) $value, null];
        }

        $text = is_string($value) ? str_replace([',', ' '], '', $value) : '';

        return ctype_digit($text) && strlen($text) <= 15
            ? [(int) $text, null]
            : [null, 'Must be a whole number.'];
    }

    private function date(mixed $value): array
    {
        if ($value instanceof DateTimeInterface) {
            return [$value->format('Y-m-d'), null];
        }

        if (is_string($value)) {
            // The date part is enough; a time after it (00:00:00) is left out.
            $text = preg_replace('/[ T]\d{1,2}:\d{2}(:\d{2})?(\.\d+)?$/', '', $value);

            foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'd.m.Y', 'Y/m/d', 'j M Y', 'j-M-Y', 'j-M-y', 'j F Y', 'd/m/y'] as $format) {
                $date = DateTimeImmutable::createFromFormat('!'.$format, $text);

                if ($date !== false && $date->format($format) === $text && (int) $date->format('Y') >= 2000) {
                    return [$date->format('Y-m-d'), null];
                }
            }
        }

        return [null, 'Not a date. Write it as 2025-07-14 or 14/07/2025.'];
    }

    private function hasProblemIn(array $problems, string $column): bool
    {
        $heading = $this->columns()[$column]['headings'][0];

        return array_filter($problems, fn ($problem) => $problem['column'] === $heading) !== [];
    }

    private function shown(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            default => mb_substr((string) $value, 0, 120),
        };
    }

    // "Sr. No" and "sr no" are the same heading.
    public static function normal(string $heading): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', strtolower($heading));
    }
}
