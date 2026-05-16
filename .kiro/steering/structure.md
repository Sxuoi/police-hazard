# Project Structure

Standard Laravel layout with strict Action + Service + Repository layering. Place new code in the layer that matches its responsibility — do not collapse layers for convenience.

## Top-Level Layout

```
app/                 PHP application code (see below)
bootstrap/           Laravel bootstrap + cached packages
config/              Framework + domain config (policehazard.php)
database/
  factories/         Eloquent model factories
  migrations/        Schema + PostGIS + immutability rules
  seeders/           DatabaseSeeder and friends
public/              Web root (index.php, built assets)
resources/
  css/app.css        Tailwind v4 entry
  js/app.js          Alpine + Leaflet entry
  views/             Blade templates grouped by feature
routes/
  web.php            Admin web routes (auth + god.admin middleware)
  console.php        Artisan console closures
storage/             Framework + app storage (logs, cache, uploads)
tests/
  Unit/              Unit tests (actions, services, models)
  Feature/           HTTP + middleware + API tests
  TestCase.php       Base test case
vite.config.js       Vite + Laravel + Tailwind plugin setup
composer.json        PHP deps + setup/dev/test scripts
package.json         JS deps + build/dev scripts
.kiro/
  specs/             Feature specs (requirements / design / tasks)
  steering/          These steering docs
Police_Hazard_PRD_v2.1.md   Binding product requirements document
DOCUMENTATION.md     Engineering overview
```

## `app/` Layout and Layer Rules

```
app/
  Actions/           Invokable business operations (one class = one operation)
  Casts/             Eloquent custom casts (e.g., PostgresArray)
  Http/
    Controllers/     Thin orchestrators; resourceful naming
      Auth/          LoginController, LogoutController
    Middleware/      EnsureSakerContext, SetGodAdminContext
    Requests/        FormRequest validation (Store*, Update*)
  Models/            Eloquent models
    Concerns/        Reusable traits (HasAuditTrail, HasUuidV7, SakerScope)
  Providers/         AppServiceProvider, RepositoryServiceProvider
  Repositories/      Concrete repository implementations
    Contracts/       Repository interfaces (always depend on these)
  Services/          Cross-cutting services (Audit, Geofence, Notification,
                     SpoofingDetection, Watermark)
```

### Layer Responsibilities

- **Controllers** validate input (via FormRequest), call the relevant Action, and return a view/redirect/JSON response. No queries, no domain logic.
- **FormRequests** own authorization + validation rules for write endpoints. Prefer `Store*Request` / `Update*Request` naming.
- **Actions** are invokable (`__invoke`) classes that perform one business operation inside a DB transaction where applicable. They call repositories and services — never controllers or other actions directly unless necessary.
- **Services** coordinate external concerns (geofence math via PostGIS, photo watermarking, notifications, audit writes, spoofing score). They do not own persistence.
- **Repositories** own all Eloquent queries. Controllers and Actions depend on the contract interface, bound in `RepositoryServiceProvider`.
- **Models** declare relationships, casts, attribute accessors/mutators, and apply traits. Tenant-scoped models must apply `SakerScope` and use `HasUuidV7`. Audited models apply `HasAuditTrail`.

## Views (`resources/views/`)

Views are grouped per feature: `assignments/`, `audit-logs/`, `auth/`, `dashboard/`, `locations/`, `officers/`, `operations/`, `reports/`, `zones/`, plus shared `components/` and `layouts/`. Follow the existing folder when adding feature pages; extend a layout in `layouts/` and reuse Blade components from `components/`.

## Migrations

- Filenames use the `YYYY_MM_DD_NNNNNN_create_<table>_table.php` convention.
- The PostGIS extension migration (`..._enable_postgis_extension.php`) must run before any spatial table.
- Immutability (`CREATE RULE no_update_... DO INSTEAD NOTHING`) and partial unique indexes (PH overlap) live in their respective table migrations.
- The `daily_attendance_summary` materialized view has a dedicated migration and is refreshed nightly via `pg_cron`.

## Routing

- `routes/web.php` handles admin routes behind `['auth', 'god.admin']` with resourceful controllers. `destroy` is intentionally excluded on immutable-ish resources; use explicit `archive` / `cancel` endpoints instead.
- Officer mobile API endpoints belong under `/api/*` with Sanctum (`auth:sanctum` + `EnsureSakerContext`). If `routes/api.php` is absent, register the file in `bootstrap/app.php` before adding routes.
- Ajax helper endpoints for admin wizards sit under `/ajax/*` with explicit route names (`ajax.zones-by-operation`, etc.).

## Naming Conventions

- Actions: `<Verb><Subject>Action` (e.g., `CreateOperationAction`, `AssignOfficerToLocationAction`).
- Services: `<Domain>Service` (e.g., `GeofenceService`).
- Repositories: `<Model>Repository` implementing `<Model>RepositoryInterface`.
- FormRequests: `Store<Model>Request`, `Update<Model>Request`.
- Controllers: `<Resource>Controller` (singular, e.g., `OperationController`).
- Traits in `Models/Concerns/`: `HasXxx` or `<Word>Scope`.
- Config keys: snake_case under `config/policehazard.php`; read via `config('policehazard.geofence.default_radius_meters')`.

## Testing Layout

- Unit tests mirror `app/` paths under `tests/Unit/` (e.g., `tests/Unit/Actions/CreateOperationActionTest.php`).
- Feature tests live under `tests/Feature/` grouped by HTTP surface (web vs api).
- The PHPUnit config forces SQLite in-memory; PostGIS-only behavior belongs in dedicated tests that either skip on non-Postgres drivers or run against a Postgres test database.
