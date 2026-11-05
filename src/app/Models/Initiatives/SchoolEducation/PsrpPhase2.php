<?php
namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

class PsrpPhase2 extends Model
{
    protected $table = 'public_school_reorganization_phase_2';

    protected function casts(): array
    {
        return [
            'visited_at' => 'date',

            'handover_status' => 'boolean',
            'furniture_available' => 'boolean',
            'clean_drinking_water_available' => 'boolean',
            'boundary_wall_available' => 'boolean',

            'students_at_handover' => 'integer',
            'students_enrolled' => 'integer',
            'total_washrooms' => 'integer',
            'total_classrooms' => 'integer',
            'total_desks' => 'integer',
            'total_room_chairs' => 'integer',
            'total_air_fans' => 'integer',
            'dangerous_classrooms' => 'integer',

            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
