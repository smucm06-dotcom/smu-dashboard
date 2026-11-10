<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\InitiativeFileController;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase1Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase2Controller;
use App\Http\Controllers\Initiatives\SpecialEducation\SpecialEducationInstitutesController;
// Login routes
Route::get('/', [AuthController::class, 'showLogin'])
    ->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.submit');

// Protected routes
Route::middleware('auth')->group(function () {
    // Departments
    Route::get('/departments', [DepartmentController::class, 'index'])
        ->name('departments.index');

    Route::get('/departments/{department}', [DepartmentController::class, 'show'])
    ->name('departments.show');

    Route::get('/initiatives/{initiative}', [InitiativeController::class, 'show'])
    ->name('initiatives.show');

    Route::post('/initiatives/{initiative}/files', [InitiativeFileController::class, 'store'])
    ->name('initiatives.files.store');

    Route::get('/initiatives/{initiative}/files/{file}/download', [InitiativeFileController::class, 'download'])
    ->scopeBindings()
    ->name('initiatives.files.download');

    Route::delete('/initiatives/{initiative}/files/{file}', [InitiativeFileController::class, 'destroy'])
    ->scopeBindings()
    ->name('initiatives.files.destroy');

    Route::get('/initiatives/{initiative}/psrp-phase-1/data', [PsrpPhase1Controller::class, 'data'])
    ->name('initiatives.psrp-phase-1.data');

    Route::get('/initiatives/{initiative}/psrp-phase-1/export', [PsrpPhase1Controller::class, 'export'])
    ->name('initiatives.psrp-phase-1.export');

    Route::post('/initiatives/{initiative}/psrp-phase-1/map-download', [PsrpPhase1Controller::class, 'mapDownload'])
    ->name('initiatives.psrp-phase-1.map-download');

    Route::post('/initiatives/{initiative}/psrp-phase-1/import', [PsrpPhase1Controller::class, 'import'])
    ->name('initiatives.psrp-phase-1.import');

    Route::get('/initiatives/{initiative}/psrp-phase-2/data', [PsrpPhase2Controller::class, 'data'])
    ->name('initiatives.psrp-phase-2.data');

    Route::get('/initiatives/{initiative}/psrp-phase-2/export', [PsrpPhase2Controller::class, 'export'])
    ->name('initiatives.psrp-phase-2.export');

    Route::post('/initiatives/{initiative}/psrp-phase-2/map-download', [PsrpPhase2Controller::class, 'mapDownload'])
    ->name('initiatives.psrp-phase-2.map-download');

    Route::post('/initiatives/{initiative}/psrp-phase-2/import', [PsrpPhase2Controller::class, 'import'])
    ->name('initiatives.psrp-phase-2.import');

    // Special Education Institutes
    Route::get('/initiatives/{initiative}/special-education-institutes/data', [SpecialEducationInstitutesController::class, 'data'])
    ->name('initiatives.special-education-institutes.data');

    Route::get('/initiatives/{initiative}/special-education-institutes/export', [SpecialEducationInstitutesController::class, 'export'])
    ->name('initiatives.special-education-institutes.export');

    Route::post('/initiatives/{initiative}/special-education-institutes/map-download', [SpecialEducationInstitutesController::class, 'mapDownload'])
    ->name('initiatives.special-education-institutes.map-download');

    Route::post('/initiatives/{initiative}/special-education-institutes/import', [SpecialEducationInstitutesController::class, 'import'])
    ->name('initiatives.special-education-institutes.import');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

});
