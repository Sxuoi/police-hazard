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

    // ── Fitur 110 (Public/Guest) ────────────────────────────────────
    Route::get('/laporan-110/isi/{token}', [\App\Http\Controllers\Report110PamaptaController::class, 'show'])
        ->name('pamapta.report.show');
    Route::post('/laporan-110/isi/{token}/arrive', [\App\Http\Controllers\Report110PamaptaController::class, 'arrive'])
        ->name('pamapta.report.arrive');
    Route::post('/laporan-110/isi/{token}/location', [\App\Http\Controllers\Report110PamaptaController::class, 'updateLocation'])
        ->name('pamapta.report.location');
    Route::post('/laporan-110/isi/{token}/unlock', [\App\Http\Controllers\Report110PamaptaController::class, 'unlock'])
        ->name('pamapta.report.unlock');
    Route::post('/laporan-110/isi/{token}/complete', [\App\Http\Controllers\Report110PamaptaController::class, 'complete'])
        ->name('pamapta.report.complete');
});

/*
|--------------------------------------------------------------------------
| Authenticated Admin Routes
|--------------------------------------------------------------------------
| All admin routes require authentication (web guard) + God Admin context middleware.
| SakerScope is automatically applied via Eloquent global scope.
*/
Route::middleware(['auth:web', 'god.admin'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/map-data', [DashboardController::class, 'mapData'])->name('dashboard.map-data');

    // ── Operations ──────────────────────────────────────────────────
    Route::resource('operations', OperationController::class)
        ->except(['destroy']);
    Route::post('operations/{operation}/archive', [OperationController::class, 'archive'])
        ->name('operations.archive');

    // ── Sakers / Hierarchy Management ────────────────────────────────
    Route::resource('sakers', \App\Http\Controllers\SakerController::class);

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
    Route::get('ajax/officer-search', [AssignmentController::class, 'officerSearch'])
        ->name('ajax.officer-search');



    // ── Reports ──────────────────────────────────────────────────────
    Route::get('reports', [ReportController::class, 'index'])
        ->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])
        ->name('reports.export');

    // ── Fitur 110: Manajemen Unit ────────────────────────────────────
    Route::resource('units', \App\Http\Controllers\UnitController::class);

    // ── Fitur 110: Dashboard Operator 110 ────────────────────────────
    Route::get('operator-110', [\App\Http\Controllers\Report110OperatorController::class, 'index'])->name('operator-110.index');
    Route::post('operator-110', [\App\Http\Controllers\Report110OperatorController::class, 'store'])->name('operator-110.store');
    Route::get('operator-110/monitor', [\App\Http\Controllers\Report110OperatorController::class, 'monitor'])->name('operator-110.monitor');
    Route::get('operator-110/{report}', [\App\Http\Controllers\Report110OperatorController::class, 'show'])->name('operator-110.show');
    Route::put('operator-110/{report}', [\App\Http\Controllers\Report110OperatorController::class, 'update'])->name('operator-110.update');
    Route::delete('operator-110/{report}', [\App\Http\Controllers\Report110OperatorController::class, 'destroy'])->name('operator-110.destroy');

    // ── God Admin Heatmap & Audit Logs ───────────────────────────────
    Route::middleware(['god.admin.strict'])->group(function () {
        Route::get('heatmap', [\App\Http\Controllers\HeatmapController::class, 'index'])->name('heatmap');
        Route::get('api/v1/admin/heatmap/data', [\App\Http\Controllers\HeatmapController::class, 'data'])->name('admin.heatmap.data');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

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
Route::prefix('officer')->name('officer.')->group(function () {
    Route::get('/', fn () => view('officer.login'))->name('home');
    Route::get('/login', [App\Http\Controllers\Auth\OfficerLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\OfficerLoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [App\Http\Controllers\Auth\OfficerLoginController::class, 'logout'])->name('logout');

    Route::get('/assignments', fn () => view('officer.assignments.index'))->name('assignments');
    Route::get('/assignments/{id}', fn () => view('officer.assignments.show'))->name('assignments.show');
    Route::get('/checkin/{assignmentId}', fn () => view('officer.checkin'))->name('checkin');
    Route::get('/bypass/{bypassId?}', fn () => view('officer.bypass'))->name('bypass');
    Route::get('/history', fn () => view('officer.history.index'))->name('history');
});
