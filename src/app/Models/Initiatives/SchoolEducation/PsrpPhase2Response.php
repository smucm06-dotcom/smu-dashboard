<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

class PsrpPhase2Response extends Model
{
    protected $table = 'public_school_reorganization_phase_2_responses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'responded_at' => 'date',
        ];
    }
}
