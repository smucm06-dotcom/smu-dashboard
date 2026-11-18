<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

// The department's response to one remark in our report on a school.
class AdditionalClassroomResponse extends Model
{
    protected $table = 'additional_classrooms_responses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source_sr' => 'integer',
            'visit_date' => 'date',
            'responded_at' => 'date',
        ];
    }
}
