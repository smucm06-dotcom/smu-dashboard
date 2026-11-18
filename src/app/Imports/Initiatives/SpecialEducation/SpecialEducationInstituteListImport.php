<?php

namespace App\Imports\Initiatives\SpecialEducation;

use App\Imports\Initiatives\SchoolEducation\PsrpPhase1Districts;
use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SpecialEducation\SpecialEducationInstitute;
use App\Models\Initiatives\SpecialEducation\SpecialEducationResponse;
use RuntimeException;

// The department's list of institutes.
class SpecialEducationInstituteListImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'list_code' => ['code', 'sr', 'sr no'],
        'emis_code' => ['emis code', 'emis', 'emis no', 'emis number'],
        'institution_name' => ['institution name', 'name of institution', 'institute name'],
        'zone' => ['zone'],
        'district' => ['district', 'district name'],
        'latitude' => ['latitude', 'lat'],
        'longitude' => ['longitude', 'long', 'lng', 'lon'],
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['emis_code'], self::MAX_ROWS);

        $rows = [];

        foreach ($sheet['rows'] as $row) {
            $emis = $this->emisCode($row['emis_code'] ?? null);
            $district = SpecialEducationDistricts::normalize($row['district'] ?? null);
            $key = $emis.'|'.$district;

            // A row with no usable code, such as a title or total line, is not an institute.
            // The list gives 8010103 to an institute in Sahiwal and one in Sargodha, so both are
            // kept. A code repeated in the same district is the same institute, kept once.
            if ($emis === null || isset($rows[$key])) {
                continue;
            }

            $rows[$key] = [
                'emis_code' => $emis,
                'list_code' => is_numeric($row['list_code'] ?? null) && (float) $row['list_code'] >= 0 ? (int) $row['list_code'] : null,
                'institution_name' => $this->cleanText($row['institution_name'] ?? null, 255),
                'zone' => ($zone = $this->cleanText($row['zone'] ?? null, 50)) === null ? null : PsrpPhase1Districts::title($zone),
                'district' => $district,
                'latitude' => PsrpPhase1Districts::coordinate($row['latitude'] ?? null, 27.7, 34.1),
                'longitude' => PsrpPhase1Districts::coordinate($row['longitude'] ?? null, 69.3, 75.5),
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('No institutes with an EMIS code were found in this file.');
        }

        return ['rows' => array_values($rows), 'sheet' => $sheet['sheet']];
    }

    // The new list replaces the old one. A failed upload never reaches this point.
    public function save(array $prepared, InitiativeFile $file): void
    {
        SpecialEducationInstitute::query()->delete();

        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            SpecialEducationInstitute::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }

        // Replies uploaded before the list can now be matched to their institute.
        SpecialEducationResponse::linkToInstitutes();
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column, such as "EMIS Code".';
    }
}
