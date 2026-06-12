# Project Inventory: Police Hazard

This document lists all active routes, controllers, models, migrations, middlewares, services, jobs, events, policies, and pages found in the project.

---

## 1. Routes (`routes/`)

Routes are registered via `bootstrap/app.php` and defined in the `routes/` directory. No API routes (`api.php`) are currently present.

### Guest Routes (`routes/web.php`)
*   `GET /login` &rarr; `Auth\LoginController@showLoginForm` (Name: `login`)
*   `POST /login` &rarr; `Auth\LoginController@login`

### Authenticated Admin Routes (`routes/web.php` &mdash; Middleware: `['auth', 'god.admin']`)
*   `POST /logout` &rarr; `Auth\LogoutController@logout` (Name: `logout`)
*   `GET /` &rarr; Redirects to dashboard (`dashboard` route)
*   `GET /dashboard` &rarr; `DashboardController@index` (Name: `dashboard`)
*   `GET /dashboard/map-data` &rarr; `DashboardController@mapData` (Name: `dashboard.map-data`)

#### Operations Module
*   `GET /operations` &rarr; `OperationController@index` (Name: `operations.index`)
*   `GET /operations/create` &rarr; `OperationController@create` (Name: `operations.create`)
*   `POST /operations` &rarr; `OperationController@store` (Name: `operations.store`)
*   `GET /operations/{operation}` &rarr; `OperationController@show` (Name: `operations.show`)
*   `GET /operations/{operation}/edit` &rarr; `OperationController@edit` (Name: `operations.edit`)
*   `PUT/PATCH /operations/{operation}` &rarr; `OperationController@update` (Name: `operations.update`)
*   `POST /operations/{operation}/archive` &rarr; `OperationController@archive` (Name: `operations.archive`)

#### Zones Module
*   `GET /zones` &rarr; `ZoneController@index` (Name: `zones.index`)
*   `GET /zones/create` &rarr; `ZoneController@create` (Name: `zones.create`)
*   `POST /zones` &rarr; `ZoneController@store` (Name: `zones.store`)
*   `GET /zones/{zone}` &rarr; `ZoneController@show` (Name: `zones.show`)
*   `GET /zones/{zone}/edit` &rarr; `ZoneController@edit` (Name: `zones.edit`)
*   `PUT/PATCH /zones/{zone}` &rarr; `ZoneController@update` (Name: `zones.update`)
*   `DELETE /zones/{zone}` &rarr; `ZoneController@destroy` (Name: `zones.destroy`)

#### Locations Module
*   `GET /locations` &rarr; `LocationController@index` (Name: `locations.index`)
*   `GET /locations/create` &rarr; `LocationController@create` (Name: `locations.create`)
*   `POST /locations` &rarr; `LocationController@store` (Name: `locations.store`)
*   `GET /locations/{location}` &rarr; `LocationController@show` (Name: `locations.show`)
*   `GET /locations/{location}/edit` &rarr; `LocationController@edit` (Name: `locations.edit`)
*   `PUT/PATCH /locations/{location}` &rarr; `LocationController@update` (Name: `locations.update`)

#### Officers Module
*   `GET /officers` &rarr; `OfficerController@index` (Name: `officers.index`)
*   `GET /officers/create` &rarr; `OfficerController@create` (Name: `officers.create`)
*   `POST /officers` &rarr; `OfficerController@store` (Name: `officers.store`)
*   `GET /officers/{officer}` &rarr; `OfficerController@show` (Name: `officers.show`)
*   `GET /officers/{officer}/edit` &rarr; `OfficerController@edit` (Name: `officers.edit`)
*   `PUT/PATCH /officers/{officer}` &rarr; `OfficerController@update` (Name: `officers.update`)

#### Assignments Module
*   `GET /assignments` &rarr; `AssignmentController@index` (Name: `assignments.index`)
*   `GET /assignments/create` &rarr; `AssignmentController@create` (Name: `assignments.create`)
*   `POST /assignments` &rarr; `AssignmentController@store` (Name: `assignments.store`)
*   `GET /assignments/{assignment}` &rarr; `AssignmentController@show` (Name: `assignments.show`)
*   `POST /assignments/{assignment}/cancel` &rarr; `AssignmentController@cancel` (Name: `assignments.cancel`)

#### Ajax Helpers (Assignment Wizard)
*   `GET /ajax/zones-by-operation` &rarr; `AssignmentController@zonesByOperation` (Name: `ajax.zones-by-operation`)
*   `GET /ajax/locations-by-zone` &rarr; `AssignmentController@locationsByZone` (Name: `ajax.locations-by-zone`)
*   `GET /ajax/shifts-by-location` &rarr; `AssignmentController@shiftsByLocation` (Name: `ajax.shifts-by-location`)
*   `GET /ajax/officer-search` &rarr; `AssignmentController@officerSearch` (Name: `ajax.officer-search`)

#### Audit Logs Module
*   `GET /audit-logs` &rarr; `AuditLogController@index` (Name: `audit-logs.index`)

#### Reports Module
*   `GET /reports` &rarr; `ReportController@index` (Name: `reports.index`)
*   `GET /reports/export` &rarr; `ReportController@export` (Name: `reports.export`)

### Console Routes (`routes/console.php`)
*   `inspire` &mdash; Displays an inspiring quote.

---

## 2. Controllers (`app/Http/Controllers/`)

*   **`Controller`** (`Controller.php`) &mdash; Base Laravel controller class.
*   **`Auth\LoginController`** (`Auth/LoginController.php`) &mdash; Renders and processes the administrator sign-in form.
*   **`Auth\LogoutController`** (`Auth/LogoutController.php`) &mdash; Processes web session termination.
*   **`AssignmentController`** (`AssignmentController.php`) &mdash; Manages CRUD operations and helper endpoints for officer-location assignments.
*   **`AuditLogController`** (`AuditLogController.php`) &mdash; Renders system-wide audit records (read-only grid).
*   **`DashboardController`** (`DashboardController.php`) &mdash; Fetches active operations summary and outputs JSON map markers.
*   **`LocationController`** (`LocationController.php`) &mdash; Manages CRUD operations for duty checkpoints (patrol points).
*   **`OfficerController`** (`OfficerController.php`) &mdash; Handles CRUD operations for police officer profile accounts.
*   **`OperationController`** (`OperationController.php`) &mdash; Handles CRUD operations for security operations (e.g., PH or PATROL).
*   **`ReportController`** (`ReportController.php`) &mdash; Prepares tabular data for summaries and exports CSV metrics.
*   **`ZoneController`** (`ZoneController.php`) &mdash; Handles CRUD operations for administrative boundaries/zones.

---

## 3. Models (`app/Models/`)

*   **`Saker`** (`Saker.php`) &mdash; Root organization/tenant (e.g., POLDA, POLRES, POLSEK) with parent-child hierarchical relations.
*   **`User`** (`User.php`) &mdash; Database accounts categorized by roles (`god_admin`, `saker_admin`, `officer`).
*   **`Operation`** (`Operation.php`) &mdash; Active deployment operations grouped under a specific Saker unit.
*   **`Zone`** (`Zone.php`) &mdash; Grouping entity between operations and locations.
*   **`Location`** (`Location.php`) &mdash; Geospatial patrol checkpoints stored as spatial geometries.
*   **`Shift`** (`Shift.php`) &mdash; Scheduled time intervals mapped to individual checkpoint targets.
*   **`Assignment`** (`Assignment.php`) &mdash; Binds User (officer) ↔ Location ↔ Shift ↔ Operation.
*   **`Attendance`** (`Attendance.php`) &mdash; Immutable check-in events including GPS telemetry and spoofing scores.
*   **`AttendanceAmendment`** (`AttendanceAmendment.php`) &mdash; Logs of admin modifications/annotations of attendance records.
*   **`AuditLog`** (`AuditLog.php`) &mdash; Read-only audit events representing user and system actions.
*   **`ManualBypassApproval`** (`ManualBypassApproval.php`) &mdash; Authorized allowances for off-schedule/out-of-bounds check-ins.
*   **`Notification`** (`Notification.php`) &mdash; custom in-app notifications generated for user accounts.

### Shared Concerns / Traits (`app/Models/Concerns/`)
*   **`HasAuditTrail`** (`HasAuditTrail.php`) &mdash; Automated registration of creation/update operations in the audit log.
*   **`HasUuidV7`** (`HasUuidV7.php`) &mdash; Automatically assigns time-ordered UUIDv7 strings as primary keys.
*   **`SakerScope`** (`SakerScope.php`) &mdash; Enforces row-level multi-tenant separation using Eloquent's global scopes.

---

## 4. Migrations (`database/migrations/`)

*   `0001_01_01_000000_create_sakers_table.php` &mdash; Standardizes initial tenant structures.
*   `0001_01_01_000001_create_users_table.php` &mdash; Provisions users and authentication tables.
*   `0001_01_01_000002_create_cache_table.php` &mdash; Creates backend framework cache schemas.
*   `0001_01_01_000003_create_jobs_table.php` &mdash; Prepares database tables for queue/worker payloads.
*   `2026_05_09_000001_enable_postgis_extension.php` &mdash; Mounts PostGIS spatial capabilities inside PostgreSQL.
*   `2026_05_09_000003_create_operations_table.php` &mdash; Defines fields for deployment operations.
*   `2026_05_09_000004_create_zones_table.php` &mdash; Outlines structural zones.
*   `2026_05_09_000005_create_locations_table.php` &mdash; Builds checkpoint tables incorporating geometry-based fields.
*   `2026_05_09_000006_create_shifts_table.php` &mdash; Defines shift schedules.
*   `2026_05_09_000007_create_assignments_table.php` &mdash; Establishes mapping for officer schedules.
*   `2026_05_09_000008_create_manual_bypass_approvals_table.php` &mdash; Manages approval entries for exception windows.
*   `2026_05_09_000009_create_attendances_table.php` &mdash; Stores final GPS check-in reports.
*   `2026_05_09_000010_create_attendance_amendments_table.php` &mdash; Tracks correction records.
*   `2026_05_09_000011_create_notifications_table.php` &mdash; Sets up notification logging.
*   `2026_05_09_000012_create_audit_logs_table.php` &mdash; Provisions audit trail tables.
*   `2026_05_09_000013_create_daily_attendance_summary_view.php` &mdash; Compiles a spatial SQL view summarizing daily attendance.

---

## 5. Middleware (`app/Http/Middleware/`)

*   **`EnsureSakerContext`** (`EnsureSakerContext.php`) &mdash; Verifies and locks in the Saker unit isolation for the logged-in administrator.
*   **`SetGodAdminContext`** (`SetGodAdminContext.php`) &mdash; Identifies `god_admin` actors to bypass Saker tenant filters.

### Middleware Aliases (`bootstrap/app.php`)
*   `'saker.context'` &rarr; `\App\Http\Middleware\EnsureSakerContext::class`
*   `'god.admin'`     &rarr; `\App\Http\Middleware\SetGodAdminContext::class`

---

## 6. Services (`app/Services/`)

*   **`AuditService`** (`AuditService.php`) &mdash; Dispatches event details into the `audit_logs` tables.
*   **`GeofenceService`** (`GeofenceService.php`) &mdash; Compares coordinate points against checkpoint radiuses using PostGIS spatial functions (`ST_Distance` and `ST_DWithin`).
*   **`NotificationService`** (`NotificationService.php`) &mdash; Dispatcher for administrative notices (*stub state*).
*   **`SpoofingDetectionService`** (`SpoofingDetectionService.php`) &mdash; Evaluates device accuracy flags and timestamp drifts (*stub state*).
*   **`WatermarkService`** (`WatermarkService.php`) &mdash; Image watermarker to draw verification overlays onto uploads (*stub state*).

---

## 7. Jobs

*   **None currently exist in the codebase.**
    *   *(Note: The PRD files refer to `ProcessCheckinPhoto`, but the job class has not been instantiated).*

---

## 8. Events

*   **None currently exist in the codebase.**

---

## 9. Policies

*   **None currently exist in the codebase.**

---

## 10. Pages / Views (`resources/views/`)

*   `welcome` (`welcome.blade.php`) &mdash; Landing page.

### Layouts & Components
*   `layouts/admin` (`layouts/admin.blade.php`) &mdash; Master panel skeleton.
*   `components/alert` (`components/alert.blade.php`) &mdash; Notification alert cards.
*   `components/badge` (`components/badge.blade.php`) &mdash; Inline status label badges.
*   `components/card` (`components/card.blade.php`) &mdash; Rounded layout wrappers.
*   `components/sidebar-item` (`components/sidebar-item.blade.php`) &mdash; Admin sidebar links.

### Admin Modules
*   **Dashboard** (`dashboard/`):
    *   `index.blade.php` &mdash; Central monitoring map and main statistics summary.
*   **Operations** (`operations/`):
    *   `index.blade.php` &mdash; Table listing current security operations.
    *   `create.blade.php` &mdash; Admin setup form for new operations.
    *   `edit.blade.php` &mdash; Update operational dates or states.
    *   `show.blade.php` &mdash; Detail page displaying child zones and status.
*   **Zones** (`zones/`):
    *   `index.blade.php` &mdash; List of active zones.
    *   `create.blade.php` &mdash; Zone registration form.
    *   `edit.blade.php` &mdash; Form for zone modification.
    *   `show.blade.php` &mdash; Detailed layout listing checkpoint assignments.
*   **Locations** (`locations/`):
    *   `index.blade.php` &mdash; Patrol checkpoint coordinates lists.
    *   `create.blade.php` &mdash; Form to set up a geospatial geofence point.
    *   `edit.blade.php` &mdash; Redefine coordinates, boundaries, and hours.
    *   `show.blade.php` &mdash; Location details including schedule lists.
*   **Officers** (`officers/`):
    *   `index.blade.php` &mdash; Grid view of police staff accounts.
    *   `create.blade.php` &mdash; Setup screen for adding new personnel.
    *   `edit.blade.php` &mdash; Edit credentials, role type, or avatar files.
    *   `show.blade.php` &mdash; Individual profile view listing assignments.
*   **Assignments** (`assignments/`):
    *   `index.blade.php` &mdash; active assignment records.
    *   `create.blade.php` &mdash; Wizard interface to bind officer &rarr; operation &rarr; shift.
    *   `show.blade.php` &mdash; Detailed overview showing active duty checkpoints.
*   **Audit Logs** (`audit-logs/`):
    *   `index.blade.php` &mdash; Listing audit trails.
*   **Reports** (`reports/`):
    *   `index.blade.php` &mdash; Filters and download selectors for report exports.
*   **Auth** (`auth/`):
    *   `login.blade.php` &mdash; Admin login form screen.

---

## 11. Additional Layers (For Completeness)

### Actions (`app/Actions/`)
*   **`CreateOperationAction`** (`CreateOperationAction.php`) &mdash; Contains database logic to register operations and dispatch creation audits.
*   **`CreateZoneAction`** (`CreateZoneAction.php`) &mdash; Provisions zones and locks parent operation configuration types.
*   **`UpdateOperationAction`** (`UpdateOperationAction.php`) &mdash; Updates operation details with type mutability validation.

### Repositories (`app/Repositories/`)
Eloquent data abstraction layer separating controllers from database queries:
*   Interfaces (`app/Repositories/Contracts/`):
    *   `SakerRepositoryInterface.php`, `UserRepositoryInterface.php`, `OperationRepositoryInterface.php`, `ZoneRepositoryInterface.php`, `LocationRepositoryInterface.php`, `AssignmentRepositoryInterface.php`, `AttendanceRepositoryInterface.php`, `AuditLogRepositoryInterface.php`, `NotificationRepositoryInterface.php`, `ShiftRepositoryInterface.php`.
*   Implementations (`app/Repositories/`):
    *   `SakerRepository.php`, `UserRepository.php`, `OperationRepository.php`, `ZoneRepository.php`, `LocationRepository.php`, `AssignmentRepository.php`, `AttendanceRepository.php`, `AuditLogRepository.php`, `NotificationRepository.php`, `ShiftRepository.php`.

### Custom Eloquent Casts (`app/Casts/`)
*   **`PostgresArray`** (`PostgresArray.php`) &mdash; Hydrates PostgreSQL numeric arrays (`SMALLINT[]`) into PHP numeric arrays.

### Custom Form Requests (`app/Http/Requests/`)
*   `Auth\LoginRequest.php` &mdash; Validates form fields and handles login rate-limiting checks.
*   `StoreOperationRequest.php` &mdash; Validation constraints for new operation records.
*   `UpdateOperationRequest.php` &mdash; Validation constraints for updating operation details.

### Service Providers (`app/Providers/`)
*   `RepositoryServiceProvider.php` &mdash; Service binding registry link interfaces to repository implementations.
*   `AppServiceProvider.php` &mdash; Global service configurations.
