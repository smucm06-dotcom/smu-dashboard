<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\InitiativeFileController;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase1Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\PsrpPhase2Controller;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
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

            // Uploading and deleting: admins only.
            Route::middleware('can:manage,initiative')->group(function () {
                Route::post('/files', [InitiativeFileController::class, 'store'])
                    ->name('initiatives.files.store');

                Route::delete('/files/{file}', [InitiativeFileController::class, 'destroy'])
                    ->scopeBindings()
                    ->name('initiatives.files.destroy');

                Route::post('/psrp-phase-1/import', [PsrpPhase1Controller::class, 'import'])
                    ->name('initiatives.psrp-phase-1.import');

                Route::post('/psrp-phase-2/import', [PsrpPhase2Controller::class, 'import'])
                    ->name('initiatives.psrp-phase-2.import');
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
