<?php

namespace App\Models\Initiatives\LocalGovernment;

use Illuminate\Database\Eloquent\Model;

// The department's response for one district named in our report or letter.
class UcMcResponse extends Model
{
    protected $table = 'lgcd_uc_mc_office_service_delivery_responses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source_sr' => 'integer',
            'smu_report_date' => 'date',
            'letter_date' => 'date',
            'cases_reported' => 'integer',
            'cases_admitted' => 'integer',
            'cases_denied' => 'integer',
            'cases_justified' => 'integer',
            'cases_unaddressed' => 'integer',
        ];
    }
}
