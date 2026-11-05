<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\PsrpPhase1School;

class PsrpPhase1SchoolListImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    // Contact numbers and CNICs in the department's file are deliberately not read.
    private const COLUMNS = [
        'emis_code' => ['emis code', 'emis', 'emis no', 'emis number'],
        'school_name' => ['school name', 'name of school'],
        'district' => ['district', 'district name'],
        'tehsil' => ['tehsil'],
        'category' => ['category'],
        'operator' => ['licensee name', 'licensee', 'operator'],
        'handover_status' => ['handing over status', 'handover status'],
        'remarks' => ['remarks issues', 'remarks'],
        'latitude' => ['lat', 'latitude'],
        'longitude' => ['long', 'lng', 'lon', 'longitude'],
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

            // A row with no usable code, such as a total line, is not a school.
            if ($emis === null) {
                continue;
            }

            $rows[$emis] ??= [
                'emis_code' => $emis,
                'school_name' => $this->cleanText($row['school_name'] ?? null, 255),
                'district' => PsrpPhase1Districts::normalize($row['district'] ?? null),
                'tehsil' => ($tehsil = $this->cleanText($row['tehsil'] ?? null, 100)) === null ? null : PsrpPhase1Districts::title($tehsil),
                'category' => $this->cleanText($row['category'] ?? null, 100),
                'operator' => $this->cleanText($row['operator'] ?? null, 255),
                'handover_status' => $this->cleanText($row['handover_status'] ?? null, 50),
                'remarks' => $this->cleanText($row['remarks'] ?? null),
                'latitude' => PsrpPhase1Districts::coordinate($row['latitude'] ?? null, 27.7, 34.1),
                'longitude' => PsrpPhase1Districts::coordinate($row['longitude'] ?? null, 69.3, 75.5),
            ];
        }

        if ($rows === []) {
            throw new \RuntimeException('No schools with an EMIS code were found in this file.');
        }

        return ['rows' => array_values($rows), 'sheet' => $sheet['sheet']];
    }

    // The new list replaces the old one. A failed upload never reaches this point.
    public function save(array $prepared, InitiativeFile $file): void
    {
        PsrpPhase1School::query()->delete();

        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            PsrpPhase1School::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column, such as "EMIS Code".';
    }
}
