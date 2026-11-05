<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\PsrpPhase2School;
use RuntimeException;

class PsrpPhase2SchoolListImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    // The inventory's headings sit in the two rows under the main heading row.
    private const SUB_HEADING_ROWS = 2;

    // What the department writes when it has no value for a school.
    private const NO_VALUE = ['null', 'n/a', 'na', '0'];

    // Contact numbers and CNICs in the department's file are deliberately not read.
    private const COLUMNS = [
        'emis_code' => ['emis code', 'emis', 'emis no', 'emis number'],
        'app_id' => ['app id', 'application id'],
        'district' => ['district', 'district name'],
        'tehsil' => ['tehsil'],
        'school_name' => ['school name', 'name of school'],
        'category' => ['category'],
        'students_enrolled' => ['student enrollment as reported by pmiu', 'student enrollment'],
        'computers' => ['no of computers'],
        'washrooms' => ['no of washrooms'],
        'classrooms' => ['no of classrooms'],
        'desks_per_classroom' => ['no of desk per classroom', 'no of desks per classroom'],
        'chairs_per_classroom' => ['no of chairs per classroom'],
        'fans_per_classroom' => ['no of fans per classroom'],
        'clean_water_available' => ['water availability'],
        'water_source_1' => ['water source 1'],
        'water_source_2' => ['water source 2'],
        'operator' => ['licensee name', 'licensee', 'operator'],
        'remarks' => ['remarks issues', 'remarks'],
        'latitude' => ['lat', 'latitude'],
        'longitude' => ['long', 'lng', 'lon', 'longitude'],
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['emis_code'], self::MAX_ROWS, self::SUB_HEADING_ROWS);

        $rows = [];

        foreach ($sheet['rows'] as $row) {
            $emis = $this->emisCode($row['emis_code'] ?? null);

            // A row with no usable code, such as a sub-heading or total line, is not a school.
            if ($emis === null) {
                continue;
            }

            $rows[$emis] ??= [
                'emis_code' => $emis,
                'app_id' => $this->text($row['app_id'] ?? null, 20),
                'school_name' => $this->text($row['school_name'] ?? null, 255),
                'district' => PsrpPhase1Districts::normalize($row['district'] ?? null),
                'tehsil' => ($tehsil = $this->text($row['tehsil'] ?? null, 100)) === null ? null : PsrpPhase1Districts::title($tehsil),
                'category' => $this->text($row['category'] ?? null, 100),
                'operator' => $this->text($row['operator'] ?? null, 255),
                'students_enrolled' => $this->wholeNumber($row['students_enrolled'] ?? null),
                'computers' => $this->wholeNumber($row['computers'] ?? null),
                'washrooms' => $this->wholeNumber($row['washrooms'] ?? null),
                'classrooms' => $this->wholeNumber($row['classrooms'] ?? null),
                'desks_per_classroom' => $this->wholeNumber($row['desks_per_classroom'] ?? null),
                'chairs_per_classroom' => $this->wholeNumber($row['chairs_per_classroom'] ?? null),
                'fans_per_classroom' => $this->wholeNumber($row['fans_per_classroom'] ?? null),
                'clean_water_available' => $this->yesOrNo($row['clean_water_available'] ?? null),
                'water_source_1' => $this->text($row['water_source_1'] ?? null, 100),
                'water_source_2' => $this->text($row['water_source_2'] ?? null, 100),
                'remarks' => $this->text($row['remarks'] ?? null),
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
        PsrpPhase2School::query()->delete();

        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            PsrpPhase2School::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    private function text(mixed $value, ?int $max = null): ?string
    {
        $text = $this->cleanText($value, $max);

        return in_array(mb_strtolower((string) $text), self::NO_VALUE, true) ? null : $text;
    }

    private function wholeNumber(mixed $value): ?int
    {
        return is_numeric($value) && (float) $value >= 0 ? (int) $value : null;
    }

    private function yesOrNo(mixed $value): ?bool
    {
        return match (mb_strtolower(trim(is_scalar($value) ? (string) $value : ''))) {
            'yes' => true,
            'no' => false,
            default => null,
        };
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column, such as "EMIS Code".';
    }
}
