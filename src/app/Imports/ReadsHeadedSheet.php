<?php

namespace App\Imports;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use RuntimeException;

trait ReadsHeadedSheet
{
    // Finds the sheet and heading row holding the wanted columns, and returns its rows
    // as arrays keyed by those columns. $columns is [key => headings it may appear under].
    // $subHeadingRows is how many rows under the heading row may hold more of the headings.
    private function readHeadedSheet(SpreadsheetReader $reader, string $path, string $extension, array $columns, array $required, int $maxRows, int $subHeadingRows = 0): array
    {
        $findHeader = fn (array $rows) => $this->findHeading($rows, $columns, $required, $subHeadingRows);

        $sheet = $reader->readSheet($path, $extension, $maxRows, fn (array $firstRows) => $findHeader($firstRows) !== null);

        if ($sheet['rows'] === []) {
            throw new RuntimeException('This file has no rows.');
        }

        $header = $findHeader($sheet['rows']);

        if ($header === null) {
            throw new RuntimeException($this->missingHeadingsMessage());
        }

        $rows = [];

        foreach ($sheet['rows'] as $number => $values) {
            if ($number <= $header['row']) {
                continue;
            }

            $row = [];

            foreach ($header['columns'] as $index => $key) {
                $row[$key] = $values[$index] ?? null;
            }

            $rows[$number] = $row;
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    abstract private function missingHeadingsMessage(): string;

    private function findHeading(array $rows, array $columns, array $required, int $subHeadingRows = 0): ?array
    {
        $keyOfHeading = [];

        foreach ($columns as $key => $headings) {
            foreach ($headings as $heading) {
                $keyOfHeading[$heading] = $key;
            }
        }

        foreach (array_slice($rows, 0, 10, true) as $number => $values) {
            $found = [];

            foreach ($values as $index => $heading) {
                $key = is_string($heading) ? ($keyOfHeading[$this->normalizeHeading($heading)] ?? null) : null;

                if ($key !== null && ! in_array($key, $found, true)) {
                    $found[$index] = $key;
                }
            }

            if (array_diff($required, $found) === []) {
                for ($next = $number + 1; $next <= $number + $subHeadingRows; $next++) {
                    foreach ($rows[$next] ?? [] as $index => $heading) {
                        $key = is_string($heading) ? ($keyOfHeading[$this->normalizeHeading($heading)] ?? null) : null;

                        if ($key !== null && ! in_array($key, $found, true)) {
                            $found[$index] = $key;
                        }
                    }
                }

                return ['row' => $number, 'columns' => $found];
            }
        }

        return null;
    }

    private function normalizeHeading(string $heading): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', strtolower($heading)));
    }

    // Excel may hold an EMIS code as a number. Returns null when it isn't a usable code.
    private function emisCode(mixed $value): ?string
    {
        $code = is_float($value) && floor($value) === $value ? (string) (int) $value : trim((string) (is_scalar($value) ? $value : ''));

        return preg_match('/^[0-9A-Za-z-]{1,20}$/D', $code) === 1 ? $code : null;
    }

    private function date(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', trim($value), $parts) === 1 && checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3])) {
            return Carbon::create((int) $parts[3], (int) $parts[2], (int) $parts[1])->format('Y-m-d');
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', trim($value)) === 1) {
            return substr(trim($value), 0, 10);
        }

        return null;
    }

    private function cleanText(mixed $value, ?int $max = null): ?string
    {
        $text = is_scalar($value) ? trim((string) $value) : ($value instanceof DateTimeInterface ? $value->format('Y-m-d') : '');

        if ($text === '' || $text === '-') {
            return null;
        }

        return $max === null ? $text : mb_substr($text, 0, $max);
    }
}
