<?php

use App\Imports\Initiatives\SchoolEducation\AdditionalClassroomResponseImport;
use App\Imports\Initiatives\SchoolEducation\AdditionalClassroomSchoolListImport;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase1ResponseImport;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase1SchoolListImport;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase2ResponseImport;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase2SchoolListImport;
use App\Imports\Initiatives\SpecialEducation\SpecialEducationInstituteListImport;
use App\Imports\Initiatives\SpecialEducation\SpecialEducationResponseImport;
use App\Models\InitiativeFile;

// For each initiative's table, the file types whose rows are saved as well as the file.
return [
    'public_school_reorganization_phase_1' => [
        InitiativeFile::TYPE_DEPARTMENT_LIST => PsrpPhase1SchoolListImport::class,
        InitiativeFile::TYPE_DEPARTMENT_RESPONSE => PsrpPhase1ResponseImport::class,
    ],
    'public_school_reorganization_phase_2' => [
        InitiativeFile::TYPE_DEPARTMENT_LIST => PsrpPhase2SchoolListImport::class,
        InitiativeFile::TYPE_DEPARTMENT_RESPONSE => PsrpPhase2ResponseImport::class,
    ],
    'special_education_institutes' => [
        InitiativeFile::TYPE_DEPARTMENT_LIST => SpecialEducationInstituteListImport::class,
        InitiativeFile::TYPE_DEPARTMENT_RESPONSE => SpecialEducationResponseImport::class,
    ],
    'additional_classrooms' => [
        InitiativeFile::TYPE_DEPARTMENT_LIST => AdditionalClassroomSchoolListImport::class,
        InitiativeFile::TYPE_DEPARTMENT_RESPONSE => AdditionalClassroomResponseImport::class,
    ],
];
