<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

class PsrpPhase2School extends Model
{
    protected $table = 'public_school_reorganization_phase_2_schools';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'clean_water_available' => 'boolean',
        ];
    }
}
