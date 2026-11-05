<?php

use App\Imports\Initiatives\SchoolEducation\PsrpPhase1ResponseImport;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase1SchoolListImport;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase2ResponseImport;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase2SchoolListImport;
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
];
