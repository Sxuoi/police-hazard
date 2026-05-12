# Police Hazard Documentation

## 1. Project Overview
Police Hazard is a web‑based command‑and‑control platform for Indonesian law‑enforcement agencies. It records, validates, and audits officer attendance at static checkpoints and mobile patrol routes using GPS‑verified, photo‑documented check‑ins. The system provides real‑time operational awareness, immutable audit trails, multi‑tenant data isolation, and reporting/heat‑map dashboards.

## 2. Technologies / Stack
- **Backend**: PHP 8.3 with Laravel 11 (service‑action‑repository pattern, Eloquent ORM, Sanctum token auth)
- **Frontend**: Blade templates, Alpine.js, Tailwind CSS, Leaflet + OpenStreetMap for maps
- **Mobile officer UI**: Responsive Blade pages using the browser Geolocation API and MediaDevices camera
- **Database**: PostgreSQL 16 with PostGIS (spatial queries, UUID v7 primary keys, row‑level security)
- **Caching / Queues**: Redis + Laravel Horizon (photo watermarking jobs)
- **Libraries**: Intervention Image (watermark), ramsey/uuid, Laravel Pint/CS‑Fixer

## 3. Architecture Overview
- **Entry Points**
  - `public/index.php` – Laravel front controller
  - `php artisan` – CLI for migrations, queue workers, scheduled jobs
  - API namespace `app/Http/Controllers/Api` – Sanctum‑protected officer mobile endpoints (login, assignments, check‑ins)
- **Core Layers**
  - **Actions** (`app/Actions/`) – invokable classes containing business logic (e.g., `CreateOperationAction`, `ProcessCheckinAction`)
  - **Services** (`app/Services/`) – cross‑cutting concerns such as `GeofenceService`, `SpoofingDetectionService`, `WatermarkService`, `AuditService`, `NotificationService`
  - **Repositories** (`app/Repositories/` + contracts) – abstract data access; controllers depend on interfaces
  - **Models** (`app/Models/`) – UUID v7 primary keys, global `SakerScope` for tenant isolation, traits (`HasAuditTrail`, `HasUuidV7`)
  - **Middleware** – `EnsureSakerContext` (tenant enforcement), `SetGodAdminContext` (bypass flag)
  - **Jobs** – queued via Horizon (`ProcessCheckinPhoto`) for async processing
- **Data Isolation**
  - PostgreSQL Row‑Level Security (RLS)
  - Eloquent global scope (`SakerScope`)
  - Middleware enforcement

## 4. Core Modules & Responsibilities
- **Authentication** – `LoginController`, `LogoutController` (session based) and Sanctum token auth for officers
- **Dashboard** – `DashboardController` provides overview and heat‑map visualisation
- **Operations / Zones / Locations** – CRUD controllers and actions manage hierarchical assignment structures
- **Attendance** – `CheckinController` validates GPS, processes photo, invokes `ProcessCheckinAction`
- **Audit & Reporting** – `AuditService` writes immutable events; reports generated via Eloquent queries and PostGIS functions
- **Notification & Bypass** – `NotificationService` creates in‑app alerts; `SetGodAdminContext` allows privileged bypasses

## 5. Configuration & Setup
- `.env.example` – template for DB, Redis, mail, S3, etc.
- `config/app.php`, `config/database.php`, `config/cache.php`, `config/session.php` – standard Laravel config
- `config/policehazard.php` – domain‑specific defaults:
  - Timezone (WIB)
  - Default geofence radius
  - Spoofing detection thresholds
  - Photo upload limits
  - Authentication rate limits
  - Cache TTLs and escalation timers
- Queue configuration uses Redis; start workers with `php artisan horizon`
- Database migrations (`database/migrations/`) create schema, enable PostGIS, and enforce immutability on `attendances` and `audit_logs`

## 6. Running the Application
### Development
```bash
# Clone repository
git clone <repo-url>
cd Police-Hazard

# Install dependencies
composer install
npm install   # if frontend assets are built

# Copy env file
cp .env.example .env
php artisan key:generate

# Run migrations and seeders
php artisan migrate --seed

# Start queue worker
php artisan horizon &

# Serve locally
php artisan serve
```
Visit `http://127.0.0.1:8000`.

### Production
- Deploy behind Nginx/Apache pointing to `public/`
- Use PHP‑FPM, configure `supervisord` to run `php artisan horizon`
- Set up PostgreSQL with PostGIS and enable RLS rules
- Configure Redis, S3 (photo storage), and mail services in `.env`
- Use a CI/CD pipeline to run `php artisan migrate --force` on deploy

## 7. API Summary (Officer Mobile)
All mobile endpoints are under `/api/*` and guarded by Sanctum tokens.
- `POST /api/login` – officer authentication, returns token
- `GET /api/assignments` – list of current zones/locations for the officer
- `POST /api/checkin` – submit GPS coordinates, photo, optional bypass flag; triggers `ProcessCheckinAction`
- `GET /api/attendance/history` – officer's check‑in history
- `POST /api/bypass/request` – request temporary bypass (requires admin approval)

Responses are JSON with standard success/error envelopes.

## 8. Testing & Quality
- **Unit Tests** – located in `tests/Unit/` for actions, services, and models (PHPUnit)
- **Feature Tests** – `tests/Feature/` exercising HTTP routes, middleware, and API endpoints
- **Static Analysis** – PHPStan level 7, Laravel Pint for code style
- **CI** – GitHub Actions run `composer install`, `php artisan test`, `phpstan analyze`, and `php artisan pint --dry-run`
- **Performance** – PostGIS spatial indexes, Redis caching for geofence checks, Horizon job retries

## 9. Contribution Guidelines (Brief)
1. Fork the repository and create a feature branch from `main`.
2. Follow the existing coding standards (PSR‑12, Laravel Pint).
3. Write unit/feature tests for new behavior.
4. Run `composer lint` and `phpstan` locally before pushing.
5. Submit a Pull Request; CI will run the full test suite.
6. Ensure your PR includes a concise description of the change and any migration steps.

---
*Generated by Claude Code based on repository exploration.*
