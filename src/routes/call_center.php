<?php

use App\Http\Controllers\CallCenter\CallCenterController;
use App\Http\Controllers\CallCenter\CallCenterUploadController;
use App\Http\Controllers\Initiatives\HigherEducation\HonhaarScholarshipController;
use Illuminate\Support\Facades\Route;

// Call center campaigns (config/call_center.php). Loaded by routes/web.php inside its signed-in group, so
// every route here needs a signed-in user who has changed their first password.

// The Call Center menu: one card per campaign the user can open.
Route::get('/call-center', [CallCenterController::class, 'index'])
    ->name('call-center.index');

// A campaign's page opens at initiatives/{id} like every initiative (InitiativeController), so it has the
// same access checks.
Route::prefix('initiatives/{initiative}')->middleware('can:view,initiative')->group(function () {
    // CM Punjab Honhaar Scholarship (Higher Education)
    Route::get('/honhaar-scholarship/data', [HonhaarScholarshipController::class, 'data'])
        ->name('initiatives.honhaar-scholarship.data');

    Route::get('/honhaar-scholarship/export', [HonhaarScholarshipController::class, 'export'])
        ->name('initiatives.honhaar-scholarship.export');

    // The two big uploads, read in the background: admins only, like the other uploads.
    Route::middleware('can:manage,initiative')->group(function () {
        Route::post('/call-center/uploads', [CallCenterUploadController::class, 'store'])
            ->name('initiatives.call-center.uploads.store');

        Route::get('/call-center/uploads/{upload}', [CallCenterUploadController::class, 'show'])
            ->name('initiatives.call-center.uploads.show');
    });
});
