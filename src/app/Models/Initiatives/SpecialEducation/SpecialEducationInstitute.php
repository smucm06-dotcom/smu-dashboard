<?php

namespace App\Models\Initiatives\SpecialEducation;

use Illuminate\Database\Eloquent\Model;

// An institute on the department's list.
class SpecialEducationInstitute extends Model
{
    protected $table = 'special_education_institutes_list';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'list_code' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
