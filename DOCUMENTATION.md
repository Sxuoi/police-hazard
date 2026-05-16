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

### Environment Variables (PH_* keys)

The following `PH_*` environment variables control Police Hazard domain behavior:

| Variable | Default | Description |
|----------|---------|-------------|
| `PH_MAX_LOGIN_ATTEMPTS` | 5 | Max failed login attempts before lockout |
| `PH_LOCKOUT_MINUTES` | 15 | Lockout duration after max attempts |
| `PH_TOKEN_EXPIRY_HOURS` | 12 | Sanctum token lifetime for officers |
| `PH_CHECKIN_RATE_LIMIT` | 10 | Max check-in requests per minute per officer |
| `PH_BYPASS_RATE_LIMIT` | 5 | Max bypass requests per minute per officer |
| `PH_PHOTO_MAX_SIZE_MB` | 8 | Maximum photo upload size |
| `PH_PHOTO_WATERMARK_RETRY` | 3 | Number of watermark retry attempts (0 = no retry) |
| `PH_PHOTO_DISK` | s3 | Filesystem disk for processed photos |
| `PH_PHOTO_PRESIGNED_TTL_MIN` | 15 | Presigned URL expiry in minutes |
| `PH_PHOTO_PRIVATE_DISK` | local | Filesystem disk for raw (unprocessed) photos |
| `PH_PHOTO_PRIVATE_PATH` | checkin-photos | Path prefix for raw photos |
| `PH_DEFAULT_TIMEZONE` | Asia/Jakarta | Fallback timezone when location has none |
| `PH_OFFICER_API_ENABLED` | false | Feature flag for officer mobile API |

### Production

- Deploy behind Nginx/Apache pointing to `public/`
- Use PHP‑FPM, configure `supervisord` to run `php artisan horizon`
- Set up PostgreSQL with PostGIS and enable RLS rules
- Configure Redis, S3 (photo storage), and mail services in `.env`
- Use a CI/CD pipeline to run `php artisan migrate --force` on deploy

### Scheduler (Required for Production)

The Laravel scheduler must run every minute for bypass expiration and escalation to work:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

This drives two scheduled actions:
- **ExpireBypassRequestsAction** — transitions pending bypass requests past their TTL to `expired` status
- **EscalateBypassRequestsAction** — advances `escalation_level` (0→1: notify God Admins, 1→2: send email) for unhandled requests

## 7. API Summary (Officer Mobile)
All mobile endpoints are under `/api/v1/*` and guarded by Sanctum tokens (except login). Responses use `Content-Type: application/json` on success and `Content-Type: application/problem+json` (RFC 7807) on error.

### Authentication

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/api/v1/auth/login` | None (rate-limited) | Officer login via NRP + password |
| `POST` | `/api/v1/auth/logout` | `auth:sanctum` | Revoke current token |

**POST /api/v1/auth/login**
```json
// Request
{ "nrp": "123456", "password": "..." }

// 200 Response
{
  "token": "1|abc...",
  "token_expires_at": "2026-05-13T00:00:00+00:00",
  "officer": {
    "id": "uuid",
    "name": "...",
    "nrp": "123456",
    "rank": "...",
    "saker_id": "uuid",
    "saker_name": "..."
  }
}
```

### Assignments

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/api/v1/officer/assignments` | `auth:sanctum` | List officer's assignments (today ± 7 days) |
| `GET` | `/api/v1/officer/assignments/{id}` | `auth:sanctum` | Assignment detail with location/shift info |
| `GET` | `/api/v1/officer/assignments/{id}/distance` | `auth:sanctum` | Live distance from assignment location |

### Check-In

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/api/v1/officer/checkin` | `auth:sanctum` (rate-limited) | Submit GPS + photo check-in |

**POST /api/v1/officer/checkin** (multipart/form-data)
```
Fields: assignment_id, latitude, longitude, gps_accuracy, gps_altitude,
        gps_speed, gps_provider, mock_location, timestamp_device,
        device_metadata (JSON), photo (file: JPEG/PNG, max 8MB)
```
```json
// 200 Response
{
  "attendance_id": "uuid",
  "status": "verified",
  "checked_in_at": "2026-05-12T08:00:00+07:00",
  "distance_from_point": 12.5,
  "spoofing_score": 0
}
```

### Bypass Requests

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/api/v1/officer/bypass-request` | `auth:sanctum` (rate-limited) | Submit bypass request with GPS/photo bundle |
| `GET` | `/api/v1/officer/bypass-request/{id}` | `auth:sanctum` | Poll bypass request status |

**POST /api/v1/officer/bypass-request** (multipart/form-data)
```
Fields: assignment_id, reason_code, officer_note (min 20 chars),
        latitude, longitude, gps_accuracy, gps_altitude, gps_speed,
        gps_provider, mock_location, timestamp_device,
        device_metadata (JSON), photo (file)
```
```json
// 201 Response
{
  "bypass_id": "uuid",
  "status": "pending",
  "expires_at": "2026-05-12T08:15:00+07:00"
}
```

### Attendance History

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/api/v1/officer/attendance/history` | `auth:sanctum` | Paginated attendance history |
| `GET` | `/api/v1/officer/attendance/{id}` | `auth:sanctum` | Single attendance detail with presigned photo URL |

### Error Envelope (RFC 7807)

All error responses use `Content-Type: application/problem+json`:

```json
{
  "type": "https://policehazard.local/errors/OUTSIDE_GEOFENCE",
  "title": "Di luar geofence",
  "status": 422,
  "detail": "Lokasi Anda 87.3 meter dari Pos Jaga Merdeka (radius 50m).",
  "instance": "/api/v1/officer/checkin",
  "reason_code": "OUTSIDE_GEOFENCE",
  "request_id": "01HZ...",
  "bypass_eligible": true,
  "distance_meters": 87.3
}
```

**Reason Codes:**
- `INVALID_CREDENTIALS` (401) — NRP/password mismatch
- `ACCOUNT_DISABLED` (403) — inactive or non-officer account
- `TOKEN_INVALID` (401) — expired or revoked token
- `OUTSIDE_SHIFT_WINDOW` (403, bypass_eligible: true)
- `MOCK_LOCATION_DETECTED` (403, bypass_eligible: false)
- `OUTSIDE_GEOFENCE` (422, bypass_eligible: true)
- `SPOOFING_REJECTED` (422, bypass_eligible: true)
- `CHECKIN_ALREADY_COMPLETED` (409, bypass_eligible: false)
- `PHOTO_INVALID` (422, bypass_eligible: false)
- `PHOTO_TOO_LARGE` (422, bypass_eligible: false)
- `ASSIGNMENT_NOT_FOUND` (422, bypass_eligible: false)
- `RATE_LIMITED` (429) — includes `retry_after_seconds`
- `MIDDLEWARE_MISCONFIGURED` (500) — internal route config error

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
