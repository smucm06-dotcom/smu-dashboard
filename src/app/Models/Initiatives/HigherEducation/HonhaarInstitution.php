<?php

namespace App\Models\Initiatives\HigherEducation;

use Illuminate\Database\Eloquent\Model;

// One spelling of a university or college in the department's list, with the single name and type it stands
// for (from the "Universities" sheet of our calls file).
class HonhaarInstitution extends Model
{
    protected $table = 'hed_honhaar_scholarship_institutions';

    protected $guarded = ['id'];
}
