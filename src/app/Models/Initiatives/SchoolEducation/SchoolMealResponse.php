<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

// The department's response about one school named in our letter.
class SchoolMealResponse extends Model
{
    protected $table = 'punjab_schools_meal_program_responses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source_sr' => 'integer',
            'letter_date' => 'date',
            'responded_at' => 'date',
        ];
    }
}
