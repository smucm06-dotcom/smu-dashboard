<?php

namespace App\Models\Initiatives\HigherEducation;

use Illuminate\Database\Eloquent\Model;

// A scholarship student in the department's list for the CM Punjab Honhaar Scholarship.
class HonhaarStudent extends Model
{
    protected $table = 'hed_honhaar_scholarship_students';

    protected $guarded = ['id'];
}
