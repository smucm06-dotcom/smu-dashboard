<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

class PsrpPhase1Response extends Model
{
    protected $table = 'public_school_reorganization_phase_1_responses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'responded_at' => 'date',
        ];
    }
}
