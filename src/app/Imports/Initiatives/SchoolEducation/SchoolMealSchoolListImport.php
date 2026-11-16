<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\SchoolMealSchool;
use RuntimeException;

// The department's list of schools in the meal program. Every column is kept. Place names come in
// capitals and are saved in one spelling (D.G. KHAN as Dera Ghazi Khan), as on Additional Classrooms.
class SchoolMealSchoolListImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'list_sr' => ['sr', 'sr no', 's no', 'sno', 'serial no'],
        'district' => ['district', 'district name'],
        'tehsil' => ['tehsil', 'tehsil name'],
        'markaz' => ['markaz', 'markaz name'],
        'school_name' => ['school name', 'name of school', 'name'],
        'emis_code' => ['emis', 'emis code', 'school emis code', 'emis no'],
        'latitude' => ['lat', 'latitude', 'school latitude'],
        'longitude' => ['lng', 'long', 'longitude', 'lon', 'school longitude'],
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
                'district' => ($district = PsrpPhase1Districts::normalize($row['district'] ?? null)) === null ? null : mb_substr($district, 0, 100),
                'tehsil' => $this->place($row['tehsil'] ?? null, 100),
                'markaz' => $this->place($row['markaz'] ?? null, 255),
                'school_name' => $this->cleanText($row['school_name'] ?? null, 255),
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
        SchoolMealSchool::query()->delete();

        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            SchoolMealSchool::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    private function wholeNumber(mixed $value): ?int
    {
        return is_numeric($value) && (float) $value >= 0 && (float) $value <= 4294967295 ? (int) $value : null;
    }

    // Tehsil and markaz come in capitals (KOT ADU, SHAH PUR - MALE), shown as Kot Adu and Shah Pur - Male.
    private function place(mixed $value, int $max): ?string
    {
        $text = $this->cleanText($value, $max);

        return $text === null ? null : PsrpPhase1Districts::title($text);
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column, such as "school_emis_code".';
    }
}
