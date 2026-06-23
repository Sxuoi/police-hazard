# Technology Stack & Commands

## Stack

- **Language / Runtime:** PHP 8.3
- **Framework:** Laravel 13.x (composer specifies `^13.7`; historical docs reference Laravel 11 — trust `composer.json`)
- **Auth:** Laravel session auth for admin web; Laravel Sanctum (`^4.3`) token auth for officer mobile API (token bound to `device_id`)
- **Database:** PostgreSQL 16 + PostGIS 3.4 (spatial queries, Row-Level Security). SQLite in-memory is used only for PHPUnit.
- **Cache / Queue:** Redis + Laravel Horizon (async photo watermarking, dashboard cache). Dev uses `php artisan queue:listen`.
- **Frontend:** Blade templates + Alpine.js (`^3.15`) + Tailwind CSS v4 (`@tailwindcss/vite`). Vite `^8` with `laravel-vite-plugin` and Bunny fonts (Instrument Sans).
- **Maps:** Leaflet `^1.9` + `leaflet.markercluster` over OpenStreetMap tiles (no API key).
- **Image processing:** `intervention/image` v4 for server-side watermarking.
- **IDs:** `ramsey/uuid` producing UUID v7 values stored in PG `uuid` columns.
- **Storage:** S3-compatible object storage via `league/flysystem-aws-s3-v3` for check-in photos.
- **Dev tooling:** Laravel Pint (code style), Laravel Pail (log tailing), Laravel Pao, Mockery, PHPUnit 12, Faker, Collision.

## Architectural Patterns (Non-Negotiable)

- **Action + Service + Repository.** Business logic lives in invokable Action classes under `app/Actions/`; cross-cutting concerns in `app/Services/`; all data access goes through Repository interfaces in `app/Repositories/Contracts/` with implementations bound in `RepositoryServiceProvider`.
- **No business logic in Controllers or Models.** Controllers orchestrate Request validation → Action → Response. Models hold relationships, casts, scopes, and traits only.
- **No direct Eloquent in Controllers.** Query through repositories.
- **Global scopes for tenancy.** Tenant-scoped models use `SakerScope`. God Admin access is granted via `SetGodAdminContext` middleware, never by disabling the scope.
- **Immutability.** Insert-only tables (`attendances`, `audit_logs`, etc.) have PG rules blocking UPDATE/DELETE; application code must match that contract.
- **Concurrency.** Assignment creation must hold `pg_advisory_xact_lock(hashtext(officer_id || assignment_date || shift_id))` inside the same transaction as the insert.
- **Geospatial.** Coordinates are `GEOMETRY(POINT, 4326)`. Use `ST_DWithin` with the GIST index; never round-trip to PHP for distance math.
- **Audit.** Every state-changing action writes through `AuditService` to `audit_logs`.

## Domain Config

Domain-specific constants live in `config/policehazard.php`:
geofence radius, bypass TTLs (PH 15m / Patrol 30m), spoofing thresholds, photo upload limits, auth rate limits, cache TTLs, and notification escalation timers. Read from this config — do not hardcode.

## Common Commands

All commands run from the project root on Windows `cmd`.

### Setup

```cmd
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

Or the bundled Composer script:

```cmd
composer setup
```

### Development

Run the full dev stack (server + queue + logs + Vite) via the Composer script:

```cmd
composer dev
```

This starts `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`, `php artisan pail --timeout=0`, and `npm run dev` concurrently. In production, replace the queue listener with `php artisan horizon`.

Frontend-only dev server:

```cmd
npm run dev
```

### Build

```cmd
npm run build
```

### Test

```cmd
composer test
```

Equivalent to `php artisan config:clear` followed by `php artisan test`. PHPUnit config forces `DB_CONNECTION=sqlite` in-memory; PostGIS-dependent tests must either skip on non-Postgres or be run against a Postgres test DB.

Run a single suite or file:

```cmd
php artisan test --testsuite=Unit
php artisan test --filter=CheckinActionTest
```

### Lint / Format

```cmd
vendor\bin\pint
vendor\bin\pint --test
```

### Queues & Logs

```cmd
php artisan queue:listen --tries=1 --timeout=0
php artisan pail
php artisan horizon
```
