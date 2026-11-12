<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\AdditionalClassroomSchool;
use RuntimeException;

// The department's list of schools with approved additional classrooms. Every column is kept.
// Place names are saved in one spelling (D.G. KHAN as Dera Ghazi Khan), so filters match the visits.
class AdditionalClassroomSchoolListImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'list_sr' => ['sr no', 'sr', 's no', 'sno', 'serial no'],
        'emis_code' => ['emis', 'emis code', 'emis no', 'school emis code'],
        'school_name' => ['school name', 'name of school', 'name'],
        'level' => ['level', 'school level'],
        'zone' => ['zone'],
        'district' => ['district', 'district name'],
        'division' => ['division'],
        'tehsil' => ['tehsil'],
        'approved_classrooms' => ['class room count', 'classroom count', 'approved classrooms'],
        'latitude' => ['lat', 'latitude'],
        'longitude' => ['lng', 'long', 'longitude', 'lon'],
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

            // A row with no usable code, such as a title or total line, is not a school.
            // A code listed twice is the same school, kept once.
            if ($emis === null || isset($rows[$emis])) {
                continue;
            }

            $rows[$emis] = [
                'emis_code' => $emis,
                'list_sr' => $this->wholeNumber($row['list_sr'] ?? null),
                'school_name' => $this->cleanText($row['school_name'] ?? null, 255),
                'level' => $this->cleanText($row['level'] ?? null, 50),
                'zone' => $this->place($row['zone'] ?? null, 50),
                'district' => ($district = PsrpPhase1Districts::normalize($row['district'] ?? null)) === null ? null : mb_substr($district, 0, 100),
                'division' => $this->place($row['division'] ?? null, 100),
                'tehsil' => $this->place($row['tehsil'] ?? null, 100),
                'approved_classrooms' => $this->wholeNumber($row['approved_classrooms'] ?? null),
                'latitude' => PsrpPhase1Districts::coordinate($row['latitude'] ?? null, 27.7, 34.1),
                'longitude' => PsrpPhase1Districts::coordinate($row['longitude'] ?? null, 69.3, 75.5),
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('No schools with an EMIS code were found in this file.');
        }

        return ['rows' => array_values($rows), 'sheet' => $sheet['sheet']];
    }

    // The new list replaces the old one. A failed upload never reaches this point.
    public function save(array $prepared, InitiativeFile $file): void
    {
        AdditionalClassroomSchool::query()->delete();

        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            AdditionalClassroomSchool::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    private function wholeNumber(mixed $value): ?int
    {
        return is_numeric($value) && (float) $value >= 0 && (float) $value <= 4294967295 ? (int) $value : null;
    }

    // Zone, division and tehsil come in capitals (SOUTH, D.G.KHAN), shown as South and D.G.Khan.
    private function place(mixed $value, int $max): ?string
    {
        $text = $this->cleanText($value, $max);

        return $text === null ? null : PsrpPhase1Districts::title($text);
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column, such as "EMIS".';
    }
}
