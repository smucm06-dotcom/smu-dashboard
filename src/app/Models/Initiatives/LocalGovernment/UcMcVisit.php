<?php

namespace App\Models\Initiatives\LocalGovernment;

use Illuminate\Database\Eloquent\Model;

// One row of our visit file for UC MC Office Service Delivery: a visit to a UC or MC office.
class UcMcVisit extends Model
{
    protected $table = 'lgcd_uc_mc_office_service_delivery';

    protected function casts(): array
    {
        // The answers stay text, as typed.
        return [
            'visited_at' => 'datetime',
            'entry' => 'integer',
        ];
    }
}
