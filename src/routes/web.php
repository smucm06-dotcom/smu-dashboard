<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\InitiativeFileController;
use App\Http\Controllers\Initiatives\SchoolEducation\AdditionalClassroomsController;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase1Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase2Controller;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Initiatives\SchoolEducation\SchoolMealProgramController;
use App\Http\Controllers\Initiatives\LocalGovernment\UcMcOfficeServiceDeliveryController;
use App\Http\Controllers\Initiatives\SpecialEducation\SpecialEducationInstitutesController;
use App\Http\Controllers\Initiatives\HousingUrbanDevelopment\PunjabSaafPaniAuthorityController;
use App\Http\Controllers\Initiatives\LocalGovernment\PunjabDevelopmentProgramController;
// Login routes
Route::get('/', [AuthController::class, 'showLogin'])
    ->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.submit');

// Protected routes
Route::middleware(['auth', 'account.active'])->group(function () {
    // Open to everyone logged in, so a user who must change their password can reach them.
    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('settings.index');

    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])
        ->middleware('throttle:10,1')
        ->name('settings.password.update');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::middleware('password.changed')->group(function () {
        // Departments
        Route::get('/departments', [DepartmentController::class, 'index'])
            ->name('departments.index');

        Route::get('/departments/{department}', [DepartmentController::class, 'show'])
            ->middleware('can:view,department')
            ->name('departments.show');

        // Call center campaigns: the Call Center menu and the campaigns' own routes.
        require __DIR__.'/call_center.php';

        // Initiatives. Add a new initiative's routes inside this group, so they are protected too.
        Route::prefix('initiatives/{initiative}')->middleware('can:view,initiative')->group(function () {
            Route::get('/', [InitiativeController::class, 'show'])
                ->name('initiatives.show');

            Route::get('/files/{file}/download', [InitiativeFileController::class, 'download'])
                ->scopeBindings()
                ->name('initiatives.files.download');

            Route::get('/psrp-phase-1/data', [PsrpPhase1Controller::class, 'data'])
                ->name('initiatives.psrp-phase-1.data');

            Route::get('/psrp-phase-1/export', [PsrpPhase1Controller::class, 'export'])
                ->name('initiatives.psrp-phase-1.export');

            Route::post('/psrp-phase-1/map-download', [PsrpPhase1Controller::class, 'mapDownload'])
                ->name('initiatives.psrp-phase-1.map-download');

            Route::get('/psrp-phase-2/data', [PsrpPhase2Controller::class, 'data'])
                ->name('initiatives.psrp-phase-2.data');

            Route::get('/psrp-phase-2/export', [PsrpPhase2Controller::class, 'export'])
                ->name('initiatives.psrp-phase-2.export');

            Route::post('/psrp-phase-2/map-download', [PsrpPhase2Controller::class, 'mapDownload'])
                ->name('initiatives.psrp-phase-2.map-download');

            // Special Education Institutes
            Route::get('/special-education-institutes/data', [SpecialEducationInstitutesController::class, 'data'])
                ->name('initiatives.special-education-institutes.data');

            Route::get('/special-education-institutes/export', [SpecialEducationInstitutesController::class, 'export'])
                ->name('initiatives.special-education-institutes.export');

            Route::post('/special-education-institutes/map-download', [SpecialEducationInstitutesController::class, 'mapDownload'])
                ->name('initiatives.special-education-institutes.map-download');

            // Additional Classrooms (School Education)
            Route::get('/additional-classrooms/data', [AdditionalClassroomsController::class, 'data'])
                ->name('initiatives.additional-classrooms.data');

            Route::get('/additional-classrooms/export', [AdditionalClassroomsController::class, 'export'])
                ->name('initiatives.additional-classrooms.export');

            Route::post('/additional-classrooms/map-download', [AdditionalClassroomsController::class, 'mapDownload'])
                ->name('initiatives.additional-classrooms.map-download');

            // Chief Minister's Punjab Schools Meal Program (School Education)
            Route::get('/school-meal/data', [SchoolMealProgramController::class, 'data'])
                ->name('initiatives.school-meal.data');

            Route::get('/school-meal/export', [SchoolMealProgramController::class, 'export'])
                ->name('initiatives.school-meal.export');

            Route::post('/school-meal/map-download', [SchoolMealProgramController::class, 'mapDownload'])
                ->name('initiatives.school-meal.map-download');

            // UC MC Office Service Delivery (Local Government & Community Development)
            Route::get('/uc-mc-office-service-delivery/data', [UcMcOfficeServiceDeliveryController::class, 'data'])
                ->name('initiatives.uc-mc-office-service-delivery.data');

            Route::post('/uc-mc-office-service-delivery/map-download', [UcMcOfficeServiceDeliveryController::class, 'mapDownload'])
                ->name('initiatives.uc-mc-office-service-delivery.map-download');

            Route::get('/uc-mc-office-service-delivery/export', [UcMcOfficeServiceDeliveryController::class, 'export'])
                ->name('initiatives.uc-mc-office-service-delivery.export');

            // Punjab Saaf Pani Authority (Housing Urban Development & Public Health Engineering)
            Route::get('/punjab-saaf-pani-authority/export', [PunjabSaafPaniAuthorityController::class, 'export'])
                ->name('initiatives.punjab-saaf-pani-authority.export');

            Route::get('/punjab-saaf-pani-authority/data', [PunjabSaafPaniAuthorityController::class, 'data'])
                ->name('initiatives.punjab-saaf-pani-authority.data');

            Route::post('/punjab-saaf-pani-authority/map-download', [PunjabSaafPaniAuthorityController::class, 'mapDownload'])
                ->name('initiatives.punjab-saaf-pani-authority.map-download');

            // Chief Minister Punjab Development Program (Local Government & Community Development)
            Route::get('/punjab-development-program/data', [PunjabDevelopmentProgramController::class, 'data'])
                ->name('initiatives.punjab-development-program.data');

            Route::get('/punjab-development-program/export', [PunjabDevelopmentProgramController::class, 'export'])
                ->name('initiatives.punjab-development-program.export');

            Route::post('/punjab-development-program/map-download', [PunjabDevelopmentProgramController::class, 'mapDownload'])
                ->name('initiatives.punjab-development-program.map-download');

            // Anyone who can open the initiative may upload a report PDF. The controller lets only admins
            // upload the other files (InitiativeFileController::store).
            Route::post('/files', [InitiativeFileController::class, 'store'])
                ->name('initiatives.files.store');

            // Uploading and deleting: admins only.
            Route::middleware('can:manage,initiative')->group(function () {
                Route::delete('/files/{file}', [InitiativeFileController::class, 'destroy'])
                    ->scopeBindings()
                    ->name('initiatives.files.destroy');

                Route::post('/psrp-phase-1/import', [PsrpPhase1Controller::class, 'import'])
                    ->name('initiatives.psrp-phase-1.import');

                Route::post('/psrp-phase-2/import', [PsrpPhase2Controller::class, 'import'])
                    ->name('initiatives.psrp-phase-2.import');

                Route::post('/special-education-institutes/import', [SpecialEducationInstitutesController::class, 'import'])
                    ->name('initiatives.special-education-institutes.import');

                Route::post('/additional-classrooms/import', [AdditionalClassroomsController::class, 'import'])
                    ->name('initiatives.additional-classrooms.import');

                Route::post('/school-meal/import', [SchoolMealProgramController::class, 'import'])
                    ->name('initiatives.school-meal.import');

                Route::post('/uc-mc-office-service-delivery/import', [UcMcOfficeServiceDeliveryController::class, 'import'])
                    ->name('initiatives.uc-mc-office-service-delivery.import');

                Route::post('/punjab-saaf-pani-authority/import', [PunjabSaafPaniAuthorityController::class, 'import'])
                    ->name('initiatives.punjab-saaf-pani-authority.import');

                Route::post('/punjab-development-program/import', [PunjabDevelopmentProgramController::class, 'import'])
                    ->name('initiatives.punjab-development-program.import');
            });
        });

        // Users: admins only.
        Route::middleware('can:manage-users')->group(function () {
            Route::resource('users', UserController::class)->except(['show', 'destroy']);

            Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])
                ->name('users.status');
        });
    });
});
