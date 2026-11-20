<?php

namespace App\Imports\Initiatives\LocalGovernment;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\LocalGovernment\PdpCity;
use RuntimeException;

// The department's list of PDP cities with each one's scope of work, for the Chief Minister Punjab
// Development Program. Every city and every column is kept as typed; "Null" is a blank. The sheet ends with
// a TOTAL row (the cities added up) and a figure under it; a row that names no city, district or division
// is not a city, so it is left out, and the page adds the cities up itself. The whole list is replaced by
// the next one.
class PdpCityListImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;
    use ReadsUcMcValues;

    private const MAX_ROWS = 100000;

    // The largest measure the table keeps (14 digits, 6 of them after the point).
    private const LARGEST_MEASURE = 99999999;

    private const COLUMNS = [
        'list_sr' => ['sr', 'sr no', 's no', 'sno', 'serial no'],
        'city' => ['city', 'city name', 'name of city', 'town'],
        'district' => ['district', 'district name'],
        'division' => ['division', 'division name'],
        'population' => ['population'],
        'sewer_km' => ['sewer km', 'sewer', 'sewerage km'],
        'storm_drain_km' => ['storm drain km', 'storm drain', 'storm drains km'],
        'ugsts' => ['ugsts', 'ugst', 'no of ugsts'],
        'ugst_capacity_mg' => ['ugst cap mg', 'ugst capacity mg', 'ugst capacity'],
        'disposal_stations_new' => ['disposal station new', 'disposal stations new'],
        'disposal_stations_rehab' => ['disposal station rehab', 'disposal stations rehab'],
        'wwtp_mgd' => ['wwtp mgd', 'wwtp'],
        'pavement_km' => ['pavement km', 'pavement'],
        'machinery' => ['machinery'],
        'solar_kw' => ['solar kw', 'solar'],
        'street_lights' => ['street lights', 'streetlights', 'street light'],
    ];

    // [column => the file's heading, the longest value the table keeps]
    private const TEXT_COLUMNS = [
        'city' => ['City', 100],
        'district' => ['District', 100],
        'division' => ['Division', 100],
    ];

    // [column => the file's heading]: whole numbers.
    private const COUNTS = [
        'population' => 'Population',
        'ugsts' => 'UGSTs',
        'disposal_stations_new' => 'Disposal Station - New',
        'disposal_stations_rehab' => 'Disposal Station - Rehab',
        'machinery' => 'Machinery',
        'street_lights' => 'Street Lights',
    ];

    // [column => the file's heading]: numbers that can have decimals.
    private const MEASURES = [
        'sewer_km' => 'Sewer (km)',
        'storm_drain_km' => 'Storm Drain (km)',
        'ugst_capacity_mg' => 'UGST Cap (MG)',
        'wwtp_mgd' => 'WWTP (MGD)',
        'pavement_km' => 'Pavement (km)',
        'solar_kw' => 'Solar (KW)',
    ];

    // The table's column order, which is the file's.
    private const ORDER = [
        'list_sr', 'city', 'district', 'division', 'population', 'sewer_km', 'storm_drain_km', 'ugsts', 'ugst_capacity_mg',
        'disposal_stations_new', 'disposal_stations_rehab', 'wwtp_mgd', 'pavement_km', 'machinery', 'solar_kw', 'street_lights',
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        // City tells the list apart from our visit file, whose heading is "Cities".
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['city', 'district'], self::MAX_ROWS);

        $rows = [];
        $problems = [];

        foreach ($sheet['rows'] as $number => $row) {
            $text = [];
            foreach (self::TEXT_COLUMNS as $column => [$label, $max]) {
                $text[$column] = $this->fittingText($row[$column] ?? null, $max, $number, $label, $problems);
            }

            // The TOTAL row, a figure under it, or a blank line: not a city.
            if (array_filter($text, fn ($value) => $value !== null) === []) {
                continue;
            }

            $values = ['list_sr' => $this->wholeNumber($row['list_sr'] ?? null, $number, 'Sr#', $problems)] + $text;

            foreach (self::COUNTS as $column => $label) {
                $values[$column] = $this->wholeNumber($row[$column] ?? null, $number, $label, $problems);
            }

            foreach (self::MEASURES as $column => $label) {
                $values[$column] = $this->measure($row[$column] ?? null, $number, $label, $problems);
            }

            $rows[] = array_merge(array_flip(self::ORDER), $values);
        }

        $this->refuseIfProblems($problems);

        if ($rows === []) {
            throw new RuntimeException('No cities were found in this file.');
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    // The new list replaces the old one. A failed upload never reaches this point.
    public function save(array $prepared, InitiativeFile $file): void
    {
        PdpCity::query()->delete();

        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            PdpCity::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // A length, capacity or power: a number, 0 or more, kept to the 6 decimal places the table holds, or blank.
    private function measure(mixed $value, int $row, string $label, array &$problems): ?string
    {
        $text = $this->asTyped($value);

        if ($text === null) {
            return null;
        }

        $number = str_replace(',', '', $text);

        if (! is_numeric($number) || (float) $number < 0 || (float) $number > self::LARGEST_MEASURE) {
            $problems[] = "Row {$row}, {$label}: \"".mb_strimwidth($text, 0, 40, '…').'" is not a number.';

            return null;
        }

        return number_format((float) $number, 6, '.', '');
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs a "City" column and a "District" column.';
    }
}
