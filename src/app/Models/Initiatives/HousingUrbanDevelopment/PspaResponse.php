<?php

namespace App\Models\Initiatives\HousingUrbanDevelopment;

use Illuminate\Database\Eloquent\Model;

// One row of the department's response file for Punjab Saaf Pani Authority (PSPA): its reply on a
// plant in one of our reports.
class PspaResponse extends Model
{
    protected $table = 'punjab_saaf_pani_authority_responses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source_sr' => 'integer',
            'response_date' => 'date',
        ];
    }
}
