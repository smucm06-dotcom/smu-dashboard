<?php

namespace App\Models\Initiatives\LocalGovernment;

use Illuminate\Database\Eloquent\Model;

// A union council on the department's list for UC MC Office Service Delivery.
class UcMcUnionCouncil extends Model
{
    protected $table = 'lgcd_uc_mc_office_service_delivery_ucs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'list_sr' => 'integer',
        ];
    }
}
