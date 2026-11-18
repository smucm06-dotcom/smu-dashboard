<?php

namespace App\Models\Initiatives\HousingUrbanDevelopment;

use Illuminate\Database\Eloquent\Model;

// One row of our visit file for Punjab Saaf Pani Authority (PSPA): a visit to a water filtration plant.
class PspaVisit extends Model
{
    protected $table = 'punjab_saaf_pani_authority';

    protected function casts(): array
    {
        // The answers stay text, as typed.
        return [
            'source_sr' => 'integer',
            'visited_at' => 'datetime',
            'total_taps' => 'integer',
            'functional_taps' => 'integer',
            'entry' => 'integer',
        ];
    }
}
