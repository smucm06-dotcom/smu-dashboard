<?php
namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

class PsrpPhase1 extends Model
{
    protected $table = 'public_school_reorganization_phase_1';

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'staff_salary_status' => 'boolean',
            'pts_council_established' => 'boolean',
            'students_charged_fees' => 'boolean',
            'handover_status' => 'boolean',
            'boundary_wall_available' => 'boolean',
            'clean_drinking_water_available' => 'boolean',
            'free_textbooks_available' => 'boolean',
            'one_grade_per_classroom' => 'boolean',
            'furniture_available' => 'boolean',
            'first_aid_available' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
