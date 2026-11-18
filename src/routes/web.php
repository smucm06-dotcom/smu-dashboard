<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\InitiativeFileController;
use App\Http\Controllers\Initiatives\SchoolEducation\AdditionalClassroomsController;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase1Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase2Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\SchoolMealProgramController;
use App\Http\Controllers\Initiatives\LocalGovernment\UcMcOfficeServiceDeliveryController;
use App\Http\Controllers\Initiatives\SpecialEducation\SpecialEducationInstitutesController;
use App\Http\Controllers\Initiatives\HousingUrbanDevelopment\PunjabSaafPaniAuthorityController;
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

    // Additional Classrooms (School Education)
    Route::get('/initiatives/{initiative}/additional-classrooms/data', [AdditionalClassroomsController::class, 'data'])
    ->name('initiatives.additional-classrooms.data');

    Route::get('/initiatives/{initiative}/additional-classrooms/export', [AdditionalClassroomsController::class, 'export'])
    ->name('initiatives.additional-classrooms.export');

    Route::post('/initiatives/{initiative}/additional-classrooms/map-download', [AdditionalClassroomsController::class, 'mapDownload'])
    ->name('initiatives.additional-classrooms.map-download');

    Route::post('/initiatives/{initiative}/additional-classrooms/import', [AdditionalClassroomsController::class, 'import'])
    ->name('initiatives.additional-classrooms.import');

    // Chief Minister's Punjab Schools Meal Program (School Education)
    Route::get('/initiatives/{initiative}/school-meal/data', [SchoolMealProgramController::class, 'data'])
    ->name('initiatives.school-meal.data');

    Route::get('/initiatives/{initiative}/school-meal/export', [SchoolMealProgramController::class, 'export'])
    ->name('initiatives.school-meal.export');

    Route::post('/initiatives/{initiative}/school-meal/map-download', [SchoolMealProgramController::class, 'mapDownload'])
    ->name('initiatives.school-meal.map-download');

    Route::post('/initiatives/{initiative}/school-meal/import', [SchoolMealProgramController::class, 'import'])
    ->name('initiatives.school-meal.import');

    // UC MC Office Service Delivery (Local Government & Community Development)
    Route::get('/initiatives/{initiative}/uc-mc-office-service-delivery/data', [UcMcOfficeServiceDeliveryController::class, 'data'])
    ->name('initiatives.uc-mc-office-service-delivery.data');

    Route::post('/initiatives/{initiative}/uc-mc-office-service-delivery/map-download', [UcMcOfficeServiceDeliveryController::class, 'mapDownload'])
    ->name('initiatives.uc-mc-office-service-delivery.map-download');

    Route::get('/initiatives/{initiative}/uc-mc-office-service-delivery/export', [UcMcOfficeServiceDeliveryController::class, 'export'])
    ->name('initiatives.uc-mc-office-service-delivery.export');

    Route::post('/initiatives/{initiative}/uc-mc-office-service-delivery/import', [UcMcOfficeServiceDeliveryController::class, 'import'])
    ->name('initiatives.uc-mc-office-service-delivery.import');

    // Punjab Saaf Pani Authority (Housing Urban Development & Public Health Engineering)
    Route::get('/initiatives/{initiative}/punjab-saaf-pani-authority/export', [PunjabSaafPaniAuthorityController::class, 'export'])
    ->name('initiatives.punjab-saaf-pani-authority.export');

    Route::post('/initiatives/{initiative}/punjab-saaf-pani-authority/import', [PunjabSaafPaniAuthorityController::class, 'import'])
    ->name('initiatives.punjab-saaf-pani-authority.import');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

});
