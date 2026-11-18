<?php

namespace App\Models\Initiatives\HousingUrbanDevelopment;

use Illuminate\Database\Eloquent\Model;

// A water filtration plant on the department's list for Punjab Saaf Pani Authority (PSPA).
class PspaPlant extends Model
{
    protected $table = 'punjab_saaf_pani_authority_plants';

    protected $guarded = ['id'];
}
