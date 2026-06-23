<?php

use App\Http\Controllers\Api\V1\Officer\AssignmentController;
use App\Http\Controllers\Api\V1\Officer\AttendanceHistoryController;
use App\Http\Controllers\Api\V1\Officer\AuthController;
use App\Http\Controllers\Api\V1\Officer\BypassRequestController;
use App\Http\Controllers\Api\V1\Officer\CheckinController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Officer Mobile API (v1)
|--------------------------------------------------------------------------
| Design §2.1 — Sanctum-authenticated officer mobile endpoints.
| All state-changing routes additionally require saker-context.
*/

Route::prefix('v1')->group(function (): void {

    // ── Authentication ───────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:officer-login')
        ->name('api.v1.auth.login');

    Route::post('auth/logout', [AuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'saker-context'])
        ->name('api.v1.auth.logout');

    // ── Officer-scoped routes ────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'saker-context'])
        ->prefix('officer')
        ->name('api.v1.officer.')
        ->group(function (): void {

            // Assignments (R2)
            Route::get('assignments', [AssignmentController::class, 'index'])
                ->name('assignments.index');
            Route::get('assignments/{id}', [AssignmentController::class, 'show'])
                ->name('assignments.show');
            Route::get('assignments/{id}/distance', [AssignmentController::class, 'distance'])
                ->name('assignments.distance');

            // Check-in (R3)
            Route::post('checkin', [CheckinController::class, 'store'])
                ->middleware('throttle:officer-checkin')
                ->name('checkin.store');

            // Bypass request (R4)
            Route::post('bypass-request', [BypassRequestController::class, 'store'])
                ->middleware('throttle:officer-bypass')
                ->name('bypass-request.store');
            Route::get('bypass-request/{id}', [BypassRequestController::class, 'show'])
                ->name('bypass-request.show');

            // Attendance history (R6)
            Route::get('attendance/history', [AttendanceHistoryController::class, 'index'])
                ->name('attendance.history');
            Route::get('attendance/{id}', [AttendanceHistoryController::class, 'show'])
                ->name('attendance.show');
        });
});
