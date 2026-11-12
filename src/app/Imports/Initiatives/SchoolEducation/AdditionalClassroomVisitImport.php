<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Models\Initiatives\SchoolEducation\AdditionalClassroomVisit;
use InvalidArgumentException;

// Our visit file. Reading, checking and saving work as for the other visit files: every row is
// checked first, and one bad row means nothing is saved. Every column of the file is kept.
class AdditionalClassroomVisitImport extends PsrpVisitImport
{
    // The longest free text kept, well above the 32,767 characters an Excel cell can hold.
    private const LONG_TEXT = 65535;

    // Headings are matched ignoring case and punctuation, so "School EMIS Code" also matches "school_emis_code".
    private const COLUMNS = [
        'source_sr' => ['label' => 'SR', 'type' => 'integer', 'headings' => ['sr no', 's no', 'sno', 'serial', 'serial no']],
        'visited_at' => ['label' => 'Date', 'type' => 'date', 'required' => true, 'headings' => ['visit date', 'visited at']],
        'district' => ['label' => 'District', 'type' => 'district', 'max' => 100, 'headings' => ['district name']],
        'emis_code' => ['label' => 'School EMIS Code', 'type' => 'code', 'required' => true, 'max' => 20, 'headings' => ['emis code', 'emis', 'emis no']],
        'school_level' => ['label' => 'School Level', 'type' => 'text', 'max' => 50, 'headings' => ['level']],
        'school_type' => ['label' => 'School Type', 'type' => 'text', 'max' => 50, 'headings' => ['school type boys girls co educational']],
        'students_enrolled' => ['label' => 'Total Enrolled Students', 'type' => 'integer', 'headings' => ['total enrolled students register', 'students enrolled']],
        'students_present' => ['label' => 'Students Present on Visit Day', 'type' => 'integer', 'headings' => ['students present']],
        'total_teachers' => ['label' => 'Total Teachers', 'type' => 'integer', 'headings' => []],
        'teachers_present' => ['label' => 'Teachers Present at Visit', 'type' => 'integer', 'headings' => ['teachers present']],
        'non_teaching_staff' => ['label' => 'Non-Teaching Staff', 'type' => 'integer', 'headings' => []],
        'non_teaching_staff_present' => ['label' => 'Non-Teaching Staff Present', 'type' => 'integer', 'headings' => []],
        'total_classrooms' => ['label' => 'Total Classrooms', 'type' => 'integer', 'headings' => []],
        'total_washrooms' => ['label' => 'Total Washrooms', 'type' => 'integer', 'headings' => []],
        'approved_classrooms' => ['label' => 'Approved Classrooms', 'type' => 'integer', 'headings' => ['approved classrooms total']],
        'constructed_classrooms' => ['label' => 'Fully Constructed Classrooms', 'type' => 'integer', 'headings' => ['constructed classrooms']],
        'under_construction_classrooms' => ['label' => 'Under Construction Classrooms', 'type' => 'integer', 'headings' => []],
        'construction_status' => ['label' => 'Current Status', 'type' => 'text', 'max' => 50, 'headings' => ['current status of additional classrooms']],
        'not_started_reason' => ['label' => 'Reason not started', 'type' => 'text', 'max' => self::LONG_TEXT, 'headings' => ['reason if construction of additional classrooms has not started']],
        'obstruction' => ['label' => 'Any Obstruction', 'type' => 'boolean', 'headings' => ['any obstruction if construction ongoing']],
        'obstruction_type' => ['label' => 'Type of Obstruction', 'type' => 'text', 'max' => self::LONG_TEXT, 'headings' => []],
        'construction_stage' => ['label' => 'Stage of Construction', 'type' => 'text', 'max' => self::LONG_TEXT, 'headings' => []],
        'labour_present' => ['label' => 'Labor Present', 'type' => 'boolean', 'headings' => ['labour present']],
        'inspection_frequency' => ['label' => 'Inspection Frequency', 'type' => 'text', 'max' => 255, 'headings' => ['inspection frequency by officials']],
        'new_classroom_facilities' => ['label' => 'Facilities in New Classrooms', 'type' => 'text', 'max' => self::LONG_TEXT, 'headings' => []],
        'classroom_condition' => ['label' => 'Classroom Condition', 'type' => 'text', 'max' => 50, 'headings' => []],
        'washroom_condition' => ['label' => 'Washrooms / Sanitation Condition', 'type' => 'text', 'max' => 50, 'headings' => ['washroom condition', 'washrooms condition']],
        'overall_cleanliness' => ['label' => 'Overall Cleanliness', 'type' => 'text', 'max' => 50, 'headings' => []],
        'clean_drinking_water' => ['label' => 'Clean Drinking Water', 'type' => 'boolean', 'headings' => []],
        'boundary_wall_available' => ['label' => 'Boundary Wall Available', 'type' => 'boolean', 'headings' => ['boundary wall']],
        'construction_quality_satisfactory' => ['label' => 'Construction Quality Satisfactory', 'type' => 'boolean', 'headings' => ['construction quality']],
    ];

    protected function columns(): array
    {
        return self::COLUMNS;
    }

    protected function model(): string
    {
        return AdditionalClassroomVisit::class;
    }

    protected function parse(mixed $value, array $column, ?string $dateOrder): ?string
    {
        // The file marks an empty answer with a dash such as "—", which means blank, like "Null".
        if (is_string($value) && preg_match('/^[\s\x{2010}-\x{2015}\x{2212}-]+$/u', $value) === 1) {
            $value = null;
        }

        return parent::parse(is_string($value) ? trim($value) : $value, $column, $dateOrder);
    }
}
