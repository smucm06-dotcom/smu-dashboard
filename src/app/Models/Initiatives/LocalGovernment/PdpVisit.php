<?php

namespace App\Models\Initiatives\LocalGovernment;

use Illuminate\Database\Eloquent\Model;

// One row of our visit file for the Chief Minister Punjab Development Program (PDP): a visit to a city's
// work sites, dated by the SMU report it went into.
class PdpVisit extends Model
{
    protected $table = 'lgcd_punjab_development_program';

    protected function casts(): array
    {
        // The answers stay text, as typed.
        return [
            'visited_at' => 'date',
            'entry' => 'integer',
        ];
    }
}
