<?php

use App\Http\Controllers\Initiatives\HousingUrbanDevelopment\PunjabSaafPaniAuthorityController;
use App\Http\Controllers\Initiatives\LocalGovernment\PunjabDevelopmentProgramController;
use App\Http\Controllers\Initiatives\LocalGovernment\UcMcOfficeServiceDeliveryController;
use App\Http\Controllers\Initiatives\SchoolEducation\AdditionalClassroomsController;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase1Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase2Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\SchoolMealProgramController;
use App\Http\Controllers\Initiatives\SpecialEducation\SpecialEducationInstitutesController;

return [
    'public_school_reorganization_phase_1' => PsrpPhase1Controller::class,
    'public_school_reorganization_phase_2' => PsrpPhase2Controller::class,
    'special_education_institutes' => SpecialEducationInstitutesController::class,
    'additional_classrooms' => AdditionalClassroomsController::class,
    'punjab_schools_meal_program' => SchoolMealProgramController::class,
    'lgcd_uc_mc_office_service_delivery' => UcMcOfficeServiceDeliveryController::class,
    'punjab_saaf_pani_authority' => PunjabSaafPaniAuthorityController::class,
    'lgcd_punjab_development_program' => PunjabDevelopmentProgramController::class,
    // A call center campaign (see config/call_center.php).
    'hed_honhaar_scholarship' => \App\Http\Controllers\Initiatives\HigherEducation\HonhaarScholarshipController::class,
];
