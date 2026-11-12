<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

// One of our visits to a school where additional classrooms are being built.
class AdditionalClassroomVisit extends Model
{
    protected $table = 'additional_classrooms';

    protected function casts(): array
    {
        return [
            'source_sr' => 'integer',
            'visited_at' => 'date',
            'students_enrolled' => 'integer',
            'students_present' => 'integer',
            'total_teachers' => 'integer',
            'teachers_present' => 'integer',
            'non_teaching_staff' => 'integer',
            'non_teaching_staff_present' => 'integer',
            'total_classrooms' => 'integer',
            'total_washrooms' => 'integer',
            'approved_classrooms' => 'integer',
            'constructed_classrooms' => 'integer',
            'under_construction_classrooms' => 'integer',
            'obstruction' => 'boolean',
            'labour_present' => 'boolean',
            'clean_drinking_water' => 'boolean',
            'boundary_wall_available' => 'boolean',
            'construction_quality_satisfactory' => 'boolean',
        ];
    }
}
