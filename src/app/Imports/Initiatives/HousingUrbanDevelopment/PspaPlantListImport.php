<?php

namespace App\Imports\Initiatives\HousingUrbanDevelopment;

use App\Imports\InitiativeFileImport;
use App\Imports\Initiatives\LocalGovernment\ReadsUcMcValues;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaPlant;
use RuntimeException;

// The department's list of water filtration plants for Punjab Saaf Pani Authority (PSPA). Every row and
// every column is kept as typed; "Null" is a blank. A coordinate that can't be one (such as
// "74.1932.79", or 74423245 with its decimal point missing) is left blank, as on the other pages' lists,
// and the plant is still kept. The whole list is replaced by the next one.
class PspaPlantListImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;
    use ReadsUcMcValues;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'code' => ['code', 'plant code', 'site code'],
        'division' => ['division', 'division name'],
        'region' => ['region', 'zone'],
        'district' => ['district', 'district name'],
        'tehsil' => ['tehsil', 'tehsil name'],
        'site_name' => ['name of site', 'site name', 'site'],
        'latitude' => ['latitude', 'lat'],
        'longitude' => ['longitude', 'long', 'lng', 'lon'],
    ];

    // [column => the file's heading, the longest value the table keeps]
    private const TEXT_COLUMNS = [
        'code' => ['Code', 20],
        'division' => ['Division', 100],
        'region' => ['Region', 100],
        'district' => ['District', 100],
        'tehsil' => ['Tehsil', 100],
        'site_name' => ['Name of Site', 255],
    ];

    // [column => the largest a coordinate can be]
    private const COORDINATES = [
        'latitude' => 90,
        'longitude' => 180,
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        // Tehsil tells the list apart from the response file, which also has a code and a site name.
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['code', 'site_name', 'tehsil'], self::MAX_ROWS);

        $rows = [];
        $problems = [];

        foreach ($sheet['rows'] as $number => $row) {
            $values = [];

            foreach (self::TEXT_COLUMNS as $column => [$label, $max]) {
                $values[$column] = $this->fittingText($row[$column] ?? null, $max, $number, $label, $problems);
            }

            foreach (self::COORDINATES as $column => $limit) {
                $values[$column] = $this->coordinate($row[$column] ?? null, $limit);
            }

            // A blank line holds nothing to keep.
            if (array_filter($values, fn ($value) => $value !== null) === []) {
                continue;
            }

            $rows[] = $values;
        }

        $this->refuseIfProblems($problems);

        if ($rows === []) {
            throw new RuntimeException('No filtration plants were found in this file.');
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    // The new list replaces the old one. A failed upload never reaches this point.
    public function save(array $prepared, InitiativeFile $file): void
    {
        PspaPlant::query()->delete();

        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            PspaPlant::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // A coordinate as typed, to the 8 places the file gives and the table keeps, or blank when it can't be one.
    private function coordinate(mixed $value, int $limit): ?string
    {
        $text = $this->asTyped($value);

        if ($text === null || ! is_numeric($text) || abs((float) $text) > $limit) {
            return null;
        }

        return number_format((float) $text, 8, '.', '');
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs "Code", "Tehsil" and "Name of Site" columns.';
    }
}
