<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Admin Routes
|--------------------------------------------------------------------------
| All admin routes require authentication + God Admin context middleware.
| SakerScope is automatically applied via Eloquent global scope.
*/
Route::middleware(['auth', 'god.admin'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Operations ──────────────────────────────────────────────────
    Route::resource('operations', \App\Http\Controllers\OperationController::class)
        ->except(['destroy']);
    Route::post('operations/{operation}/archive', [\App\Http\Controllers\OperationController::class, 'archive'])
        ->name('operations.archive');

    // ── Zones ───────────────────────────────────────────────────────
    Route::resource('zones', \App\Http\Controllers\ZoneController::class);

    // ── Locations ────────────────────────────────────────────────────
    Route::resource('locations', \App\Http\Controllers\LocationController::class)
        ->except(['destroy']);

    // ── Officers ─────────────────────────────────────────────────────
    Route::resource('officers', \App\Http\Controllers\OfficerController::class)
        ->except(['destroy']);

    // ── Assignments ──────────────────────────────────────────────────
    Route::resource('assignments', \App\Http\Controllers\AssignmentController::class)
        ->except(['edit', 'update', 'destroy']);
    Route::post('assignments/{assignment}/cancel', [\App\Http\Controllers\AssignmentController::class, 'cancel'])
        ->name('assignments.cancel');

    // Ajax helpers for assignment wizard
    Route::get('ajax/zones-by-operation', [\App\Http\Controllers\AssignmentController::class, 'zonesByOperation'])
        ->name('ajax.zones-by-operation');
    Route::get('ajax/locations-by-zone', [\App\Http\Controllers\AssignmentController::class, 'locationsByZone'])
        ->name('ajax.locations-by-zone');
    Route::get('ajax/shifts-by-location', [\App\Http\Controllers\AssignmentController::class, 'shiftsByLocation'])
        ->name('ajax.shifts-by-location');
    Route::get('ajax/officer-search', [\App\Http\Controllers\AssignmentController::class, 'officerSearch'])
        ->name('ajax.officer-search');

    // ── Audit Logs (read-only) ────────────────────────────────────────
    Route::get('audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->name('audit-logs.index');
});
