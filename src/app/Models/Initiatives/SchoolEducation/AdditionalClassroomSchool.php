<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

// A school on the department's list of approved additional classrooms.
class AdditionalClassroomSchool extends Model
{
    protected $table = 'additional_classrooms_schools';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'list_sr' => 'integer',
            'approved_classrooms' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
