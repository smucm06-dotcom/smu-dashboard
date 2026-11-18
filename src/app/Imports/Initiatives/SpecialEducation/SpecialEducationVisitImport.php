<?php

namespace App\Imports\Initiatives\SpecialEducation;

use App\Imports\Initiatives\SchoolEducation\PsrpVisitImport;
use App\Models\Initiatives\SpecialEducation\SpecialEducationResponse;
use App\Models\Initiatives\SpecialEducation\SpecialEducationVisit;
use InvalidArgumentException;

// Our visit file. Reading, checking and saving work as for the PSRP visit files: every row is
// checked first, and one bad row means nothing is saved.
class SpecialEducationVisitImport extends PsrpVisitImport
{
    // Headings are matched ignoring case and punctuation, so "EMIS Code" also matches "emis_code".
    private const COLUMNS = [
        'source_sr' => [
            'label' => 'Sr',
            'type' => 'integer',
            'headings' => ['sr', 'sr no', 's no', 'sno', 'serial', 'serial no'],
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
            'headings' => ['emis code', 'emis no', 'emis number'],
        ],
        'institution_name' => [
            'label' => 'Institution',
            'type' => 'text',
            'max' => 255,
            'headings' => ['name of institution', 'institution name', 'institute name'],
        ],
        'principal_present' => [
            'label' => 'Principal present',
            'type' => 'boolean',
            'headings' => [],
        ],
        'total_teachers' => [
            'label' => 'Total teachers',
            'type' => 'integer',
            'headings' => [],
        ],
        'present_teachers' => [
            'label' => 'Present teachers',
            'type' => 'integer',
            'headings' => [],
        ],
        'total_psychologists' => [
            'label' => 'Total psychologists',
            'type' => 'integer',
            'headings' => ['total psychologist'],
        ],
        'present_psychologists' => [
            'label' => 'Present psychologists',
            'type' => 'integer',
            'headings' => ['present psychologist'],
        ],
        'total_admin_staff' => [
            'label' => 'Total admin staff',
            'type' => 'integer',
            'headings' => ['total administration staff'],
        ],
        'present_admin_staff' => [
            'label' => 'Present admin staff',
            'type' => 'integer',
            'headings' => ['present administration staff'],
        ],
        'bus_driver_post_vacant' => [
            'label' => 'Bus driver post vacant',
            'type' => 'boolean',
            'headings' => ['vacant position school buss driver', 'vacant position school bus driver'],
        ],
        'students_enrolled' => [
            'label' => 'Students enrolled',
            'type' => 'integer',
            'headings' => ['total enrolled students'],
        ],
        'students_present' => [
            'label' => 'Students present',
            'type' => 'integer',
            'headings' => ['present students'],
        ],
        'fee_charged' => [
            'label' => 'Fee charged',
            'type' => 'boolean',
            'headings' => ['fee from students'],
        ],
        'monthly_stipend' => [
            'label' => 'Monthly stipend',
            'type' => 'boolean',
            'headings' => [],
        ],
        'total_classrooms' => [
            'label' => 'Classrooms',
            'type' => 'integer',
            'headings' => ['total classrooms'],
        ],
        'total_washrooms' => [
            'label' => 'Washrooms',
            'type' => 'integer',
            'headings' => ['total washrooms'],
        ],
        'separate_classroom_per_grade' => [
            'label' => 'Classroom for each grade',
            'type' => 'boolean',
            'headings' => ['separate classrooms for each grade'],
        ],
        'washroom_cleanliness' => [
            'label' => 'Washroom cleanliness',
            'type' => 'title',
            'max' => 50,
            'headings' => ['cleanliness of washrooms'],
        ],
        'classroom_cleanliness' => [
            'label' => 'Classroom cleanliness',
            'type' => 'title',
            'max' => 50,
            'headings' => ['cleanliness of classrooms'],
        ],
        'free_books' => [
            'label' => 'Free books',
            'type' => 'boolean',
            'headings' => [],
        ],
        'free_uniform' => [
            'label' => 'Free uniform',
            'type' => 'boolean',
            'headings' => [],
        ],
        // Yes, No, or "Transport service not available", so it is kept as written.
        'free_transport' => [
            'label' => 'Free transport',
            'type' => 'text',
            'max' => 50,
            'headings' => ['free school transport'],
        ],
        'furniture_available' => [
            'label' => 'Furniture',
            'type' => 'boolean',
            'headings' => [],
        ],
        'first_aid_available' => [
            'label' => 'First aid',
            'type' => 'boolean',
            'headings' => ['first aid facility'],
        ],
        'clean_drinking_water_available' => [
            'label' => 'Clean drinking water',
            'type' => 'boolean',
            'headings' => [],
        ],
        'boundary_wall_available' => [
            'label' => 'Boundary wall',
            'type' => 'boolean',
            'headings' => [],
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
        return SpecialEducationVisit::class;
    }

    public function run(string $path, string $extension): array
    {
        $result = parent::run($path, $extension);

        // Replies uploaded before this visit can now be matched to their institute.
        if (! isset($result['failure'])) {
            SpecialEducationResponse::linkToInstitutes();
        }

        return $result;
    }

    protected function findHeader(array $rows): ?array
    {
        // The "%" columns are worked out from the counts, so they are not read. Leaving them out
        // also stops "% Present Students" being taken for "Present Students".
        $rows = array_map(
            fn (array $values) => array_map(
                fn ($value) => is_string($value) && str_starts_with(trim($value), '%') ? null : $value,
                $values
            ),
            array_slice($rows, 0, 10, true)
        );

        $header = parent::findHeader($rows);

        if ($header === null || in_array('district', $header['columns'], true)) {
            return $header;
        }

        // In our visit file the district column has no heading. It is the one just before EMIS Code.
        $emisIndex = array_search('emis_code', $header['columns'], true);
        $before = $emisIndex - 1;

        if ($before >= 0 && ! isset($header['columns'][$before]) && blank($rows[$header['number']][$before] ?? null)) {
            $header['columns'][$before] = 'district';
            ksort($header['columns']);
        }

        return $header;
    }

    protected function parse(mixed $value, array $column, ?string $dateOrder): ?string
    {
        if ($column['type'] === 'district') {
            $district = SpecialEducationDistricts::normalize($value);

            if ($district !== null && mb_strlen($district) > $column['max']) {
                throw new InvalidArgumentException("Must be {$column['max']} characters or fewer.");
            }

            return $district;
        }

        // A count written with words, such as "Current Enrolment 78", is read as its one number.
        if ($column['type'] === 'integer' && is_string($value) && preg_match('/\pL/u', $value) === 1
            && preg_match('/^\D*?(\d+)\D*$/u', $value, $match) === 1) {
            $value = $match[1];
        }

        return parent::parse($value, $column, $dateOrder);
    }
}
