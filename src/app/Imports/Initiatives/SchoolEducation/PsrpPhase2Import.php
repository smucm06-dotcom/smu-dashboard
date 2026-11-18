<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Models\Initiatives\SchoolEducation\PsrpPhase2;
use App\Models\Initiatives\SchoolEducation\PsrpPhase2SchoolName;
use InvalidArgumentException;

class PsrpPhase2Import extends PsrpVisitImport
{
    private const ENROLLMENT_COLUMNS = ['students_at_handover', 'students_enrolled'];

    // Headings are matched ignoring case and punctuation, so "EMIS Code" also matches "emis_code".
    private const COLUMNS = [
        'source_sr' => [
            'label' => 'SR',
            'type' => 'integer',
            'headings' => ['sr', 'sr no', 's no', 'sno', 'serial', 'serial no', 'serial number'],
        ],
        'visited_at' => [
            'label' => 'Visit date',
            'type' => 'date',
            'required' => true,
            'headings' => ['date', 'visit date', 'timestamp'],
        ],
        'district' => [
            'label' => 'District',
            'type' => 'district',
            'max' => 100,
            'headings' => ['district name'],
        ],
        'emis_code' => [
            'label' => 'EMIS',
            'type' => 'code',
            'required' => true,
            'max' => 20,
            'headings' => ['emis', 'emis no', 'emis number', 'school emis', 'school emis code'],
        ],
        'handover_status' => [
            'label' => 'Handed over',
            'type' => 'boolean',
            'headings' => ['handing over status', 'handover status', 'school handed over'],
        ],
        'furniture_available' => [
            'label' => 'Furniture',
            'type' => 'boolean',
            'headings' => ['furniture availability', 'furniture available'],
        ],
        'students_at_handover' => [
            'label' => 'At handover',
            'type' => 'integer',
            'headings' => ['student enrollment before handing over', 'students at handover'],
        ],
        'students_enrolled' => [
            'label' => 'Enrolled now',
            'type' => 'integer',
            'headings' => ['student enrollment after handing over', 'students enrolled'],
        ],
        'total_washrooms' => [
            'label' => 'Washrooms',
            'type' => 'integer',
            'headings' => ['total washrooms'],
        ],
        'total_classrooms' => [
            'label' => 'Classrooms',
            'type' => 'integer',
            'headings' => ['total classrooms'],
        ],
        'total_desks' => [
            'label' => 'Desks',
            'type' => 'integer',
            'headings' => ['total desks'],
        ],
        'total_room_chairs' => [
            'label' => 'Room chairs',
            'type' => 'integer',
            'headings' => ['total room chairs'],
        ],
        'total_air_fans' => [
            'label' => 'Air fans',
            'type' => 'integer',
            'headings' => ['total air fans'],
        ],
        'classroom_condition' => [
            'label' => 'Classroom condition',
            'type' => 'title',
            'max' => 50,
            'headings' => [],
        ],
        'washroom_cleanliness' => [
            'label' => 'Washroom condition',
            'type' => 'title',
            'max' => 50,
            'headings' => ['washroom condition cleanliness'],
        ],
        'school_cleanliness' => [
            'label' => 'School cleanliness',
            'type' => 'title',
            'max' => 50,
            'headings' => ['overall cleanliness of school'],
        ],
        'clean_drinking_water_available' => [
            'label' => 'Clean water',
            'type' => 'boolean',
            'headings' => ['clean drinking water availability', 'clean drinking water'],
        ],
        'boundary_wall_available' => [
            'label' => 'Boundary wall',
            'type' => 'boolean',
            'headings' => ['boundary wall availability'],
        ],
        'dangerous_classrooms' => [
            'label' => 'Dangerous classrooms',
            'type' => 'integer',
            'headings' => [],
        ],
        'school_name' => [
            'label' => 'School name',
            'type' => 'text',
            'max' => 255,
            'headings' => ['school'],
        ],
        'latitude' => [
            'label' => 'Latitude',
            'type' => 'decimal',
            'limit' => 90,
            'blanks' => self::NO_LOCATION_VALUES,
            'headings' => ['lat'],
        ],
        'longitude' => [
            'label' => 'Longitude',
            'type' => 'decimal',
            'limit' => 180,
            'blanks' => self::NO_LOCATION_VALUES,
            'headings' => ['long', 'lng', 'lon'],
        ],
    ];

    protected function columns(): array
    {
        return self::COLUMNS;
    }

    protected function model(): string
    {
        return PsrpPhase2::class;
    }

    // Dates and districts are saved as the file has them; nothing is changed for particular schools.
    public function run(string $path, string $extension): array
    {
        $result = parent::run($path, $extension);

        if (! isset($result['failure'])) {
            $this->saveSchoolNames($path, $extension);
        }

        return $result;
    }

    // Enrollment has its own sheet in the visit file. Its dates don't always match the visit
    // sheet's, so it joins on the school alone. A file without that sheet leaves saved figures as they are.
    protected function withExtraSheets(array $records, array $columns, string $path, string $extension): array
    {
        if (array_intersect(self::ENROLLMENT_COLUMNS, $columns) !== []) {
            return [$records, $columns];
        }

        $enrollment = $this->enrollmentBySchool($path, $extension);

        if ($enrollment === null) {
            return [$records, $columns];
        }

        foreach ($records as $key => $record) {
            $records[$key] = $record + ($enrollment[$record['emis_code']] ?? array_fill_keys(self::ENROLLMENT_COLUMNS, null));
        }

        return [$records, [...$columns, ...self::ENROLLMENT_COLUMNS]];
    }

    private function enrollmentBySchool(string $path, string $extension): ?array
    {
        $header = fn (array $rows) => $this->enrollmentHeader($rows);

        $rows = $this->reader->read($path, $extension, self::MAX_ROWS, fn (array $firstRows) => $header($firstRows) !== null);
        $found = $header($rows);

        if ($found === null) {
            return null;
        }

        $emisIndex = array_search('emis_code', $found['columns'], true);
        $enrollment = [];

        foreach ($rows as $number => $values) {
            if ($number <= $found['number']) {
                continue;
            }

            try {
                $emis = $this->parse($values[$emisIndex] ?? null, self::COLUMNS['emis_code'], null);
            } catch (InvalidArgumentException) {
                continue;
            }

            $figures = array_fill_keys(self::ENROLLMENT_COLUMNS, null);

            foreach ($found['columns'] as $index => $column) {
                if (in_array($column, self::ENROLLMENT_COLUMNS, true)) {
                    $figures[$column] = $this->wholeNumberOrNothing($values[$index] ?? null, $column);
                }
            }

            $enrollment[$emis] = $figures;
        }

        return $enrollment;
    }

    private function enrollmentHeader(array $rows): ?array
    {
        $header = $this->findHeader($rows);

        return $header !== null && array_intersect(self::ENROLLMENT_COLUMNS, $header['columns']) !== [] ? $header : null;
    }

    // The EMIS Lookup sheet names every school on the list, including those not visited yet.
    private function saveSchoolNames(string $path, string $extension): void
    {
        $headingsOf = function (array $rows): ?array {
            foreach (array_slice($rows, 0, 10, true) as $number => $values) {
                $headings = array_map(fn ($heading) => is_string($heading) ? trim(strtolower($heading)) : '', $values);

                // The visit sheet has these headings too, along with a date.
                if (in_array('emis code', $headings, true) && in_array('school name', $headings, true) && ! in_array('date', $headings, true)) {
                    return ['number' => $number, 'columns' => array_flip($headings)];
                }
            }

            return null;
        };

        $rows = $this->reader->read($path, $extension, self::MAX_ROWS, fn (array $firstRows) => $headingsOf($firstRows) !== null);
        $found = $headingsOf($rows);

        if ($found === null) {
            return;
        }

        $columns = $found['columns'];
        $names = [];
        $now = now();

        foreach ($rows as $number => $values) {
            if ($number <= $found['number'] || ! is_scalar($values[$columns['emis code']] ?? null)) {
                continue;
            }

            $names[] = [
                'emis_code' => (string) $values[$columns['emis code']],
                'school_name' => mb_substr((string) ($values[$columns['school name']] ?? ''), 0, 255) ?: null,
                'latitude' => PsrpPhase1Districts::coordinate($values[$columns['lat'] ?? -1] ?? null, 27.7, 34.1),
                'longitude' => PsrpPhase1Districts::coordinate($values[$columns['long'] ?? -1] ?? null, 69.3, 75.5),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($names, 500) as $chunk) {
            PsrpPhase2SchoolName::upsert($chunk, ['emis_code'], ['school_name', 'latitude', 'longitude', 'updated_at']);
        }
    }

    // A figure that isn't a number shouldn't lose the school's visit, so it is left blank.
    private function wholeNumberOrNothing(mixed $value, string $column): ?string
    {
        try {
            return $this->parse($value, self::COLUMNS[$column], null);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
