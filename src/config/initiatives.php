<?php

use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase1Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase2Controller;
use App\Http\Controllers\Initiatives\SpecialEducation\SpecialEducationInstitutesController;

return [
    'public_school_reorganization_phase_1' => PsrpPhase1Controller::class,
    'public_school_reorganization_phase_2' => PsrpPhase2Controller::class,
    'special_education_institutes' => SpecialEducationInstitutesController::class,
];
