<?php

namespace App\Models\Initiatives\SpecialEducation;

use Illuminate\Database\Eloquent\Model;

// One of our visits to a special education institute.
class SpecialEducationVisit extends Model
{
    protected $table = 'special_education_institutes';

    protected function casts(): array
    {
        return [
            'visited_at' => 'date',
            'principal_present' => 'boolean',
            'bus_driver_post_vacant' => 'boolean',
            'fee_charged' => 'boolean',
            'monthly_stipend' => 'boolean',
            'separate_classroom_per_grade' => 'boolean',
            'free_books' => 'boolean',
            'free_uniform' => 'boolean',
            'furniture_available' => 'boolean',
            'first_aid_available' => 'boolean',
            'clean_drinking_water_available' => 'boolean',
            'boundary_wall_available' => 'boolean',
            'total_teachers' => 'integer',
            'present_teachers' => 'integer',
            'total_psychologists' => 'integer',
            'present_psychologists' => 'integer',
            'total_admin_staff' => 'integer',
            'present_admin_staff' => 'integer',
            'students_enrolled' => 'integer',
            'students_present' => 'integer',
            'total_classrooms' => 'integer',
            'total_washrooms' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
