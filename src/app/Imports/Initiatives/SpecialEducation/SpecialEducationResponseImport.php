<?php

namespace App\Imports\Initiatives\SpecialEducation;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SpecialEducation\SpecialEducationResponse;
use RuntimeException;

// The department's replies, one row per issue. Each institute's Sr.No., district, name and
// report date are written only on its first row, so they carry down to the rows under it.
// The file leaves the name out for some institutes (such as Sr 143), so a new institute starts
// on any row with its own Sr.No., district or name, and its replies are kept without a name.
class SpecialEducationResponseImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'source_sr' => ['sr no', 'sr', 's no', 'sno'],
        'district' => ['district', 'district name'],
        'institution_name' => ['institute name', 'institution name', 'name of institution'],
        'issue' => ['overall key issues', 'key issues', 'issues', 'issue'],
        'response' => ['department response', 'response', 'reply'],
        'reported_at' => ['smu report date', 'report date'],
        'responded_at' => ['department response date', 'response date', 'reply date'],
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['institution_name', 'response'], self::MAX_ROWS);

        $codes = SpecialEducationResponse::codesByName();
        $institute = null;
        $first = 0;
        $rows = [];

        // Report dates written in full, by row, for filling in a year typed with a digit missing.
        $known = [];
        foreach ($sheet['rows'] as $line => $row) {
            if (($date = $this->date($row['reported_at'] ?? null)) !== null) {
                $known[$line] = $date;
            }
        }

        foreach ($sheet['rows'] as $line => $row) {
            $sr = is_numeric($row['source_sr'] ?? null) && (float) $row['source_sr'] >= 0 ? (int) $row['source_sr'] : null;
            $name = $this->cleanText($row['institution_name'] ?? null, 255);
            $district = SpecialEducationDistricts::normalize($row['district'] ?? null);

            if ($sr !== null || $name !== null || $district !== null) {
                $first = count($rows);
                $institute = [
                    'source_sr' => $sr,
                    'district' => $district === null ? null : mb_substr($district, 0, 100),
                    'institution_name' => $name,
                    // Without a name there is nothing to find the institute by, so it stays blank.
                    'emis_code' => $name === null ? null : SpecialEducationResponse::codeFor($codes, $name, $district),
                    'reported_at' => null,
                ];
            }

            // Rows above the first institute have nothing to belong to.
            if ($institute === null) {
                continue;
            }

            $institute['reported_at'] = $this->reportDate($row['reported_at'] ?? null, $line, $known) ?? $institute['reported_at'];
            $respondedAt = $this->date($row['responded_at'] ?? null);

            // The report date is sometimes written lower down than the institute's first row.
            for ($i = $first; $institute['reported_at'] !== null && $i < count($rows); $i++) {
                $rows[$i]['reported_at'] ??= $institute['reported_at'];
            }

            $issue = $this->cleanText($row['issue'] ?? null);
            $response = $this->cleanText($row['response'] ?? null);

            // An empty line inside an institute's rows. A blank reply to an issue is still kept.
            // A reply date written on such a line (Sr 138) belongs to the rows above it.
            if ($issue === null && $response === null) {
                for ($i = $first; $respondedAt !== null && $i < count($rows); $i++) {
                    $rows[$i]['responded_at'] ??= $respondedAt;
                }

                continue;
            }

            $rows[] = $institute + [
                'issue' => $issue,
                'response' => $response,
                'responded_at' => $respondedAt,
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('No replies were found in this file.');
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    public function save(array $prepared, InitiativeFile $file): void
    {
        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            SpecialEducationResponse::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // A report date, or one typed with a digit of the year missing (07/05/202 for Sr 119). That
    // year is taken from the nearest full date above or below that starts with the same digits,
    // when the result is within a month of it. Anything else unreadable is left blank.
    private function reportDate(mixed $value, int|string $line, array $known): ?string
    {
        $date = $this->date($value);

        if ($date !== null || ! is_string($value) || preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{3})$#', trim($value), $parts) !== 1) {
            return $date;
        }

        $lines = array_keys($known);
        $before = array_values(array_filter($lines, fn ($other) => $other < $line));
        $after = array_values(array_filter($lines, fn ($other) => $other > $line));
        $neighbours = array_filter([end($before) === false ? null : $known[end($before)], $after === [] ? null : $known[$after[0]]]);

        $best = null;

        foreach ($neighbours as $neighbour) {
            $year = (int) substr($neighbour, 0, 4);

            if (! str_starts_with((string) $year, $parts[3]) || ! checkdate((int) $parts[2], (int) $parts[1], $year)) {
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

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an institute name column and a response column, such as "Institute Name" and "Department Response".';
    }
}
