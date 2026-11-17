<?php

namespace App\Imports\Initiatives\LocalGovernment;

use DateTimeInterface;
use RuntimeException;

// How the UC MC list and response uploads read a cell. Values are kept as typed. A value that can't
// be kept whole (too long, or text where a count should be) is listed, and the file is refused, so
// nothing is cut short or left out without anyone knowing.
trait ReadsUcMcValues
{
    private const LISTED_PROBLEMS = 5;

    // Written in the files for "nothing here". The visit upload reads the same words as blanks.
    private const BLANK_WORDS = ['-', '--', 'n/a', 'na', 'null', 'nl', 'nil'];

    // A cell as text, as typed, without the spaces around it. Null when it is empty or says "Null".
    private function asTyped(mixed $value): ?string
    {
        $text = match (true) {
            $value === null => '',
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_bool($value) => $value ? 'TRUE' : 'FALSE',
            is_float($value) && floor($value) === $value && abs($value) < 1e15 => (string) (int) $value,
            is_scalar($value) => (string) $value,
            default => '',
        };

        // Text that isn't valid UTF-8 is trimmed of plain spaces only, never lost.
        $text = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $text) ?? trim($text);

        return $text === '' || in_array(mb_strtolower($text), self::BLANK_WORDS, true) ? null : $text;
    }

    // Text that must fit its column. Longer text is listed as a problem instead of being cut.
    private function fittingText(mixed $value, int $max, int $row, string $label, array &$problems): ?string
    {
        $text = $this->asTyped($value);

        if ($text !== null && mb_strlen($text) > $max) {
            $problems[] = "Row {$row}, {$label}: longer than {$max} characters.";
        }

        return $text;
    }

    // A count or serial number: a whole number, 0 or more, or blank.
    private function wholeNumber(mixed $value, int $row, string $label, array &$problems): ?int
    {
        $text = $this->asTyped($value);

        if ($text === null) {
            return null;
        }

        $number = str_replace(',', '', $text);

        if (! is_numeric($number) || (float) $number < 0 || (float) $number > 4294967295 || floor((float) $number) !== (float) $number) {
            $problems[] = "Row {$row}, {$label}: \"".mb_strimwidth($text, 0, 40, '…').'" is not a whole number.';

            return null;
        }

        return (int) $number;
    }

    // Stops the upload when any row has a problem, so nothing from the file is saved.
    private function refuseIfProblems(array $problems): void
    {
        if ($problems === []) {
            return;
        }

        $count = count($problems);
        $listed = implode(' ', array_slice($problems, 0, self::LISTED_PROBLEMS));
        $more = $count > self::LISTED_PROBLEMS ? ' And '.number_format($count - self::LISTED_PROBLEMS).' more.' : '';

        throw new RuntimeException(
            ($count === 1 ? '1 value in this file has a problem' : number_format($count).' values in this file have problems')
            .', so nothing was saved. Correct the file and upload it again. '.$listed.$more
        );
    }
}
