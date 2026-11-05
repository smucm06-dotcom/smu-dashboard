<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Models\Initiatives\SchoolEducation\PsrpPhase1;

class PsrpPhase1Import extends PsrpVisitImport
{
    // Headings are matched ignoring case and punctuation, so "EMIS Code" also matches "emis_code".
    private const COLUMNS = [
        'source_sr' => [
            'label' => 'SR',
            'type' => 'integer',
            'headings' => ['sr', 'sr no', 's no', 'sno', 'serial', 'serial no', 'serial number'],
        ],
        'visited_at' => [
            'label' => 'Visit date',
            'type' => 'datetime',
            'required' => true,
            'headings' => ['timestamp', 'timestap', 'time stamp', 'visit date', 'visit date time', 'visit date and time', 'date'],
        ],
        'district' => [
            'label' => 'District',
            'type' => 'title',
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
        'staff_salary_status' => [
            'label' => 'Staff salary paid',
            'type' => 'boolean',
            'headings' => ['staff salary paid', 'salary paid', 'salary status', 'staff salary'],
        ],
        'pts_council_established' => [
            'label' => 'PTS council formed',
            'type' => 'boolean',
            'headings' => ['pts council formed', 'pts council', 'council established', 'school council established'],
        ],
        'council_meetings_count' => [
            'label' => 'Council meetings',
            'type' => 'integer',
            'headings' => ['council meetings', 'no of council meetings', 'number of council meetings', 'pts council meetings'],
        ],
        'students_charged_fees' => [
            'label' => 'Students charged fees',
            'type' => 'boolean',
            'headings' => ['charging fees', 'fee charged', 'fees charged', 'students charged fee', 'students being charged fees'],
        ],
        'students_enrolled' => [
            'label' => 'Students enrolled',
            'type' => 'integer',
            'headings' => ['enrolled now', 'total enrollment', 'total enrolment', 'current enrollment', 'current enrolment', 'enrollment', 'enrolment', 'total students enrolled'],
        ],
        'students_at_handover' => [
            'label' => 'Students at handover',
            'type' => 'integer',
            'headings' => ['at handover', 'students handover', 'enrollment at handover', 'enrolment at handover', 'students at the time of handover', 'handover enrollment', 'handover enrolment'],
        ],
        'students_present' => [
            'label' => 'Students present',
            'type' => 'integer',
            'headings' => ['present', 'students present on visit day', 'students present at visit', 'present students'],
        ],
        'total_teachers' => [
            'label' => 'Total teachers',
            'type' => 'integer',
            'headings' => ['teachers', 'no of teachers', 'number of teachers'],
        ],
        'total_classrooms' => [
            'label' => 'Total classrooms',
            'type' => 'integer',
            'headings' => ['classrooms', 'no of classrooms', 'number of classrooms'],
        ],
        'total_washrooms' => [
            'label' => 'Total washrooms',
            'type' => 'integer',
            'headings' => ['washrooms', 'no of washrooms', 'number of washrooms'],
        ],
        'dangerous_classrooms' => [
            'label' => 'Dangerous classrooms',
            'type' => 'integer',
            'headings' => ['no of dangerous classrooms', 'number of dangerous classrooms', 'dangerous rooms'],
        ],
        'handover_status' => [
            'label' => 'School handed over',
            'type' => 'boolean',
            'headings' => ['school handover status', 'handed over', 'school handed over'],
        ],
        'teachers_present' => [
            'label' => 'Teachers present',
            'type' => 'integer',
            'headings' => ['teachers present at visit', 'teachers present at visit time', 'present teachers'],
        ],
        'boundary_wall_available' => [
            'label' => 'Boundary wall',
            'type' => 'boolean',
            'headings' => ['boundary wall', 'boundary wall around school'],
        ],
        'classroom_condition' => [
            'label' => 'Classroom condition',
            'type' => 'title',
            'max' => 50,
            'headings' => ['classrooms condition', 'condition of classrooms'],
        ],
        'washroom_cleanliness' => [
            'label' => 'Washroom cleanliness',
            'type' => 'title',
            'max' => 50,
            'headings' => ['washroom condition', 'washroom condition cleanliness'],
        ],
        'school_cleanliness' => [
            'label' => 'School cleanliness',
            'type' => 'title',
            'max' => 50,
            'headings' => ['overall school cleanliness', 'cleanliness', 'school premises'],
        ],
        'clean_drinking_water_available' => [
            'label' => 'Clean drinking water',
            'type' => 'boolean',
            'headings' => ['clean drinking water', 'clean water', 'drinking water'],
        ],
        'free_textbooks_available' => [
            'label' => 'Free textbooks',
            'type' => 'boolean',
            'headings' => ['free textbooks', 'free text books', 'free textbooks from school'],
        ],
        'one_grade_per_classroom' => [
            'label' => 'One grade per classroom',
            'type' => 'boolean',
            'headings' => ['one grade classroom', 'classroom accommodate only one grade level'],
        ],
        'furniture_available' => [
            'label' => 'Furniture available',
            'type' => 'boolean',
            'headings' => ['furniture', 'furniture for students', 'furniture available for students'],
        ],
        'first_aid_available' => [
            'label' => 'First aid available',
            'type' => 'boolean',
            'headings' => ['first aid', 'first aid box', 'first aid facility available'],
        ],
        'teachers_enrolled_pef_training' => [
            'label' => 'Teachers enrolled in PEF training',
            'type' => 'integer',
            'headings' => ['teachers enrolled in pef training', 'no of teachers has enrolled in pef s training courses'],
        ],
        'teachers_nominated_pef_cpdp' => [
            'label' => 'Teachers nominated for PEF CPDP',
            'type' => 'integer',
            'headings' => ['teachers nominated for pef cpdp', 'no of teachers has nominated for the pef cpdp program'],
        ],
        'school_name' => [
            'label' => 'School name',
            'type' => 'text',
            'max' => 255,
            'headings' => ['name of school', 'school', 'name'],
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
        return PsrpPhase1::class;
    }
}
