<?php

use App\Dashboards\Initiatives\HigherEducation\HonhaarDashboard;
use App\Imports\Initiatives\HigherEducation\HonhaarCallImport;
use App\Imports\Initiatives\HigherEducation\HonhaarLinker;
use App\Imports\Initiatives\HigherEducation\HonhaarStudentListImport;
use App\Models\InitiativeFile;

// Call center campaigns: initiatives whose data comes from the call center's calls, not from SMU's field
// visits. Each is still an initiative of its department, so access, uploads and reports work as for any
// other, but it opens from the Call Center menu and is left out of the Departments pages' field visit counts.
return [
    'campaigns' => [
        // Keyed by the initiative's table_name.
        'hed_honhaar_scholarship' => [
            // The uploads read in the background, with a progress bar.
            'imports' => [
                InitiativeFile::TYPE_OUR_VISITS => HonhaarCallImport::class,
                InitiativeFile::TYPE_DEPARTMENT_LIST => HonhaarStudentListImport::class,
            ],

            // Links every call to the department's list after an upload or a deleted file.
            'linker' => HonhaarLinker::class,

            // The campaign's totals on the Call Center page (summary(): calls, people, last call date).
            'summary' => HonhaarDashboard::class,
        ],
    ],

    // A run of at least this many rows that repeats earlier rows, one after another and in the same order,
    // is a file pasted in twice: its rows are kept but not counted. Real repeat calls never line up this
    // way (in the first Honhaar file the longest such run of real calls is 13 rows).
    'copy_run_rows' => 100,

    // The largest file the two background uploads accept, in megabytes (PHP and the web server allow 100).
    'max_upload_mb' => 100,

    // The queue the background uploads run on.
    'queue' => 'call-center',
];
