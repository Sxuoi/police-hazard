<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BypassApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ZoneController;
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
    Route::get('/dashboard/map-data', [DashboardController::class, 'mapData'])->name('dashboard.map-data');

    // ── Operations ──────────────────────────────────────────────────
    Route::resource('operations', OperationController::class)
        ->except(['destroy']);
    Route::post('operations/{operation}/archive', [OperationController::class, 'archive'])
        ->name('operations.archive');

    // ── Zones ───────────────────────────────────────────────────────
    Route::resource('zones', ZoneController::class);

    // ── Locations ────────────────────────────────────────────────────
    Route::resource('locations', LocationController::class)
        ->except(['destroy']);

    // ── Officers ─────────────────────────────────────────────────────
    Route::resource('officers', OfficerController::class)
        ->except(['destroy']);

    // ── Assignments ──────────────────────────────────────────────────
    Route::resource('assignments', AssignmentController::class)
        ->except(['edit', 'update', 'destroy']);
    Route::post('assignments/{assignment}/cancel', [AssignmentController::class, 'cancel'])
        ->name('assignments.cancel');

    // Ajax helpers for assignment wizard
    Route::get('ajax/zones-by-operation', [AssignmentController::class, 'zonesByOperation'])
        ->name('ajax.zones-by-operation');
    Route::get('ajax/locations-by-zone', [AssignmentController::class, 'locationsByZone'])
        ->name('ajax.locations-by-zone');
    Route::get('ajax/shifts-by-location', [AssignmentController::class, 'shiftsByLocation'])
        ->name('ajax.shifts-by-location');
    Route::get('ajax/officer-search', [AssignmentController::class, 'officerSearch'])
        ->name('ajax.officer-search');

    // ── Audit Logs (read-only) ────────────────────────────────────────
    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->name('audit-logs.index');

    // ── Reports ──────────────────────────────────────────────────────
    Route::get('reports', [ReportController::class, 'index'])
        ->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])
        ->name('reports.export');

    // ── Bypass Approvals ─────────────────────────────────────────────
    Route::get('/bypass-approvals', [BypassApprovalController::class, 'index'])
        ->name('bypass-approvals.index');
    Route::get('/bypass-approvals/{id}', [BypassApprovalController::class, 'show'])
        ->name('bypass-approvals.show');
    Route::post('/bypass-approvals/{id}/approve', [BypassApprovalController::class, 'approve'])
        ->name('bypass-approvals.approve');
    Route::post('/bypass-approvals/{id}/deny', [BypassApprovalController::class, 'deny'])
        ->name('bypass-approvals.deny');
});

/*
|--------------------------------------------------------------------------
| Officer Mobile Web UI (public — token in sessionStorage)
|--------------------------------------------------------------------------
*/
Route::prefix('officer')->group(function () {
    Route::get('/', fn () => view('officer.login'))->name('officer.home');
    Route::get('/login', fn () => view('officer.login'))->name('officer.login');
    Route::get('/assignments', fn () => view('officer.assignments.index'))->name('officer.assignments');
    Route::get('/assignments/{id}', fn () => view('officer.assignments.show'))->name('officer.assignments.show');
    Route::get('/checkin/{assignmentId}', fn () => view('officer.checkin'))->name('officer.checkin');
    Route::get('/bypass/{bypassId?}', fn () => view('officer.bypass'))->name('officer.bypass');
    Route::get('/history', fn () => view('officer.history.index'))->name('officer.history');
});
