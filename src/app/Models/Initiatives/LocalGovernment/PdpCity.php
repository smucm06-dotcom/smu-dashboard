<?php

namespace App\Models\Initiatives\LocalGovernment;

use Illuminate\Database\Eloquent\Model;

// A city on the department's list for the Chief Minister Punjab Development Program (PDP), with its
// scope of work.
class PdpCity extends Model
{
    protected $table = 'lgcd_punjab_development_program_cities';

    protected $guarded = ['id'];
}
