<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

// A school on the department's list for the Chief Minister's Punjab Schools Meal Program.
class SchoolMealSchool extends Model
{
    protected $table = 'punjab_schools_meal_program_schools';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'list_sr' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
