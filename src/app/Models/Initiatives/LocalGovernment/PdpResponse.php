<?php

namespace App\Models\Initiatives\LocalGovernment;

use Illuminate\Database\Eloquent\Model;

// One row of the department's response file for the Chief Minister Punjab Development Program (PDP):
// an SMU report and the department's answer to it.
class PdpResponse extends Model
{
    protected $table = 'lgcd_punjab_development_program_responses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'response_letter_date' => 'date',
            'smu_report_date' => 'date',
        ];
    }
}
