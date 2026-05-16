# Requirements Document

## Introduction

Phase 3 delivers the **Mobile Officer Check-In System** — the officer-facing half of the Police Hazard platform. Phases 1 and 2 already shipped:

- Full PostgreSQL + PostGIS schema with append-only `attendances`, `manual_bypass_approvals`, and `audit_logs` (DB-level `DO INSTEAD NOTHING` rules).
- The complete admin web UI (session auth): dashboard with map, CRUD for operations/zones/locations/officers/assignments, audit-log viewer, reports + export.
- Domain services: `AuditService`, `GeofenceService`, `SpoofingDetectionService`, `WatermarkService`, `NotificationService`.
- Three-layer tenant isolation: `SakerScope` + `EnsureSakerContext` + (planned) PostgreSQL RLS.
- Domain config file `config/policehazard.php`.
- A single monolithic `DatabaseSeeder.php` (~200 lines) that seeds every entity.

Phase 3 adds:

1. An officer-facing HTTP API rooted at `/api/v1/*` (Sanctum bearer tokens, no device binding).
2. A mobile-web UI at `/officer/*` the officer uses from a phone browser — login, today's assignments, GPS+photo check-in, bypass request, attendance history.
3. A supervisor-side bypass approval queue inside the existing admin interface.
4. Location-scoped timezone handling (WIB/WITA/WIT per `locations.timezone`).
5. Splitting the monolithic seeder into ten single-entity seeder files.
6. Three narrow migrations to unblock the workflow: add `locations.timezone`; extend `manual_bypass_approvals` with the officer's submitted GPS/photo columns, add `SPOOFING_REJECTED` to the reason-code `CHECK` constraint, and replace its global `no_update` rule with a narrow pending → decided transition rule; replace the global `no_update_attendances` rule with a narrow `photo_path`/`photo_status` transition rule so the watermark worker can record its result.

**Explicit simplifications (scoped down from earlier drafts):**

- No device binding — tokens are plain Sanctum PATs.
- No JWT bypass tokens — bypass requests are just database rows created from the officer's rejected check-in bundle.
- No client-submitted checksum on check-in requests — a server-internal SHA-256 of the attendance row's fields is still computed and stored for tamper detection inside the DB (`attendances.checksum`).
- No HMAC-SHA256 signature on supervisor approval decisions — the approver_id + append-only DB rule + audit log already give you legally-defensible non-repudiation.

**Out of scope:** offline-first sync queue, native iOS/Android, payroll, any changes to the admin dashboard/reports/heatmap.

## Glossary

- **Saker**: Tenant organizational unit (POLDA, POLRESTABES, POLSEK). PRD §4.1.
- **Operation**: Deployment campaign owned by a Saker, of type `PH` (one officer per location per shift) or `PATROL` (multiple officers per location).
- **Location**: Geospatial patrol point (PostGIS `POINT`, SRID 4326) with a `radius_meters` geofence, `minimum_officer`, and (Phase 3) a `timezone` IANA identifier (e.g. `Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura`).
- **Shift**: Time-of-day window attached to a Location (`shift_start`, `shift_end`, `active_days`). Evaluated in the Location's timezone.
- **Assignment**: Officer ↔ Location ↔ Shift ↔ assignment_date binding. Today's assignments are the check-in surface.
- **Attendance**: Immutable, append-only check-in row.
- **Officer**: User with `role = 'officer'`. Authenticates through the mobile-web surface and the Sanctum API; cannot use the admin web UI.
- **Supervisor**: Saker Admin or God Admin. Uses the existing admin web UI; Phase 3 adds the Bypass Approval Queue to that UI.
- **Shift Window**: The absolute datetime range `[shift_start, shift_end]` for `assignment_date` in the Location's timezone. Midnight-spanning shifts (`shift_end < shift_start`) run from `assignment_date + shift_start` to `(assignment_date + 1 day) + shift_end`, and attendance is attributed to `assignment_date` (PRD §5.5).
- **Geofence**: `ST_DWithin(officer_coordinates, location.coordinates, location.radius_meters)` in SRID 4326.
- **Reason Code**: Machine-readable identifier on rejection responses. Defined set: `INVALID_CREDENTIALS`, `ACCOUNT_DISABLED`, `ACCOUNT_LOCKED`, `TOKEN_INVALID`, `ASSIGNMENT_NOT_FOUND`, `OUTSIDE_SHIFT_WINDOW`, `MOCK_LOCATION_DETECTED`, `OUTSIDE_GEOFENCE`, `LOW_GPS_ACCURACY` (flag-only), `TIMESTAMP_DRIFT` (flag-only), `SPOOFING_REJECTED`, `CHECKIN_ALREADY_COMPLETED`, `PHOTO_INVALID`, `PHOTO_TOO_LARGE`, `RATE_LIMITED`, `BYPASS_DECISION_ALREADY_MADE`, `BYPASS_EXPIRED`, `BYPASS_PHOTO_MISSING`, `OFFICER_NOTE_REQUIRED`, `SUPERVISOR_NOTE_REQUIRED`, `MOCK_LOCATION_NEVER_BYPASSABLE`, `INVALID_DATE_RANGE`, `UNSUPPORTED_MEDIA_TYPE`, `MIDDLEWARE_MISCONFIGURED`.
- **Bypass-Eligible Reason Codes**: `OUTSIDE_SHIFT_WINDOW`, `OUTSIDE_GEOFENCE`, `SPOOFING_REJECTED`. Every other rejection is a hard stop. `MOCK_LOCATION_DETECTED` is permanently non-bypassable per PRD §5.4.
- **Bypass Request**: A `manual_bypass_approvals` row created from the officer's rejected check-in bundle. Carries the officer's submitted coordinates, photo, device metadata, and note until the supervisor decides or it expires.
- **ProcessCheckinAction**: The single invokable Action class that runs the check-in pipeline and is the only code path authorized to insert an Attendance row.
- **Officer API**: The Sanctum-guarded HTTP API rooted at `/api/v1/auth/*` and `/api/v1/officer/*`.
- **Mobile Web UI**: Responsive Blade templates served under `/officer/*` from the same Laravel application. Uses browser Geolocation + MediaDevices camera.

## Requirements

### Requirement 1: Officer API Authentication

**User Story:** As an Officer, I want to log in from my phone with my NRP and password and receive a bearer token, so I can use the mobile-web UI to check in without tying the token to a specific device.

#### Acceptance Criteria

1. THE Officer_API SHALL expose `POST /api/v1/auth/login` accepting a JSON body with fields `nrp` (string) and `password` (string).
2. WHEN `POST /api/v1/auth/login` is called with `nrp` and `password` matching an active user whose `role = 'officer'`, THE Officer_API SHALL issue a Laravel Sanctum personal access token with `expires_at = now() + config.policehazard.auth.token_expiry_hours` hours, update `users.last_login_at`, and return HTTP 200 with `token`, `token_expires_at` (ISO 8601), and an `officer` object containing `id`, `name`, `nrp`, `saker` (id, code, name, type), `avatar_url`.
3. WHEN `POST /api/v1/auth/login` is called with credentials that do not match any user, THE Officer_API SHALL return HTTP 401 with reason code `INVALID_CREDENTIALS` and SHALL NOT reveal whether the NRP exists.
4. WHEN `POST /api/v1/auth/login` is called with correct credentials but `users.is_active = false`, THE Officer_API SHALL return HTTP 403 with reason code `ACCOUNT_DISABLED`.
5. WHEN `POST /api/v1/auth/login` is called with correct credentials but the user's `role != 'officer'`, THE Officer_API SHALL return HTTP 403 with reason code `ACCOUNT_DISABLED` and SHALL NOT leak that the role is wrong.
6. IF the same NRP or IP exceeds `config.policehazard.auth.max_login_attempts` failed login attempts within `config.policehazard.auth.lockout_minutes`, THEN THE Officer_API SHALL return HTTP 429 with reason code `ACCOUNT_LOCKED` and a `retry_after_seconds` field.
7. WHEN any `/api/v1/officer/*` endpoint is called with a missing, expired, or revoked token, THE Officer_API SHALL return HTTP 401 with reason code `TOKEN_INVALID`.
8. THE Officer_API SHALL expose `POST /api/v1/auth/logout` which, when called with a valid token, revokes that specific token via `$request->user()->currentAccessToken()->delete()` and returns HTTP 204.
9. WHEN a login succeeds, THE Officer_API SHALL write an audit event `OFFICER_LOGIN_SUCCESS` via AuditService including `actor_id`, `actor_ip`, `actor_user_agent`.
10. WHEN a login fails for reasons 1.3, 1.4, 1.5, or 1.6, THE Officer_API SHALL write an audit event `OFFICER_LOGIN_FAILED` via AuditService including the attempted `nrp` (if non-empty), `actor_ip`, `actor_user_agent`, and `reason_code`, and SHALL NOT include the attempted password in any field.
11. THE Officer_API SHALL reject any request to `POST /api/v1/auth/login` whose `Content-Type` is not `application/json` with HTTP 415 and reason code `UNSUPPORTED_MEDIA_TYPE`.
12. WHERE the environment is production, THE Officer_API SHALL require HTTPS and SHALL redirect plain HTTP to HTTPS.
13. THE Officer_API SHALL include an `X-Request-ID` response header (UUID v7) on every response for audit-log correlation.
14. WHEN a user transitions `is_active` from true to false via the existing admin UI, THE system SHALL revoke all of that user's Sanctum tokens so that subsequent `/api/v1/officer/*` calls fail with `TOKEN_INVALID`.

### Requirement 2: Officer Views Assignments

**User Story:** As an Officer, I want to see my assignments for today (and optionally ±7 days around today), so I know which locations I need to check in to and during which shift windows.

#### Acceptance Criteria

1. THE Officer_API SHALL expose `GET /api/v1/officer/assignments` which, with a valid token, returns HTTP 200 with a JSON array of the authenticated officer's assignments.
2. WHEN `GET /api/v1/officer/assignments` is called without a `date` query parameter, THE Officer_API SHALL return assignments where `assignment_date` equals today in the authenticated officer's Saker's default timezone (read from the Saker's Location with `is_primary = true` or, if none, `config.policehazard.default_timezone = 'Asia/Jakarta'`).
3. WHEN `GET /api/v1/officer/assignments` is called with `?date=YYYY-MM-DD`, THE Officer_API SHALL return assignments for that calendar date, provided it is within `± 7` days of today. The `± 7` day constraint SHALL apply only when the `date` query parameter is explicitly provided.
4. IF the `date` query parameter is explicitly provided and is either malformed or outside the `± 7` day window, THEN THE Officer_API SHALL return HTTP 422 with reason code `INVALID_DATE_RANGE`.
5. THE Officer_API SHALL exclude assignments with `status = 'cancelled'`.
6. THE Officer_API SHALL return, for each assignment: `assignment_id`, `operation_name`, `operation_type` (`PH` or `PATROL`), `zone_name`, `location_id`, `location_name`, `location_address`, `location_coordinates` (`{lat, lng}`), `location_radius_meters`, `location_timezone` (IANA), `shift_id`, `shift_name`, `shift_start` (`HH:MM`), `shift_end` (`HH:MM`), `assignment_date` (`YYYY-MM-DD`), `status`, and `already_checked_in` (boolean derived from a verified attendance on this assignment).
7. THE Officer_API SHALL return assignments sorted ascending by `shift_start`.
8. THE Officer_API SHALL expose `GET /api/v1/officer/assignments/{id}` which returns the same fields as 2.6 plus `padal_name`, `padal_phone`, and `operating_hours` for the single assignment.
9. IF `GET /api/v1/officer/assignments/{id}` is called with an `id` that does not belong to the authenticated officer or does not exist within the officer's Saker scope, THEN THE Officer_API SHALL return HTTP 404 with reason code `ASSIGNMENT_NOT_FOUND` and SHALL NOT leak the existence of the record.
10. THE Officer_API SHALL expose `GET /api/v1/officer/assignments/{id}/distance?latitude=&longitude=` returning `{ distance_meters, within_geofence }` computed via `GeofenceService` using PostGIS `ST_Distance` in WGS84.
11. THE Officer_API SHALL enforce via `SakerScope` + `EnsureSakerContext` middleware that every query in 2.1, 2.8, and 2.10 returns only assignments whose `saker_id` matches the authenticated officer's `saker_id`.

### Requirement 3: Officer Performs Check-In (Simplified 12-Step Pipeline)

**User Story:** As an Officer, I want my GPS + photo check-in to be validated against the business and security rules in a single atomic transaction so that only legitimate, verifiable attendance is recorded.

#### Acceptance Criteria

1. THE Officer_API SHALL expose `POST /api/v1/officer/checkin` accepting `Content-Type: multipart/form-data` with fields `assignment_id` (uuid), `latitude` (decimal -90..90), `longitude` (decimal -180..180), `gps_accuracy` (decimal), `gps_altitude` (decimal, optional), `gps_speed` (decimal, optional), `gps_provider` (one of `gps`, `network`, `fused`), `timestamp_device` (ISO 8601), `mock_location` (boolean), `photo` (file).
2. THE Officer_API SHALL delegate the request to a single invokable `ProcessCheckinAction` that executes steps 1 through 12 of the check-in pipeline in the order defined below.
3. **Step 1 — Token Authentication.** WHEN `POST /api/v1/officer/checkin` is called without a valid, non-expired Sanctum token, THE Officer_API SHALL return HTTP 401 with reason code `TOKEN_INVALID` and SHALL NOT write an attendance record.
4. **Step 2 — Assignment Lookup.** WHEN the token is valid, THE AssignmentLookup SHALL resolve an active, non-cancelled assignment where `assignment_id = request.assignment_id`, `officer_id = authenticated_officer.id`, `assignment_date = today` in the Location's timezone, and `saker_id = authenticated_officer.saker_id`. IF no such row exists, THEN THE Officer_API SHALL return HTTP 422 with reason code `ASSIGNMENT_NOT_FOUND` and SHALL NOT write an attendance record.
5. **Step 3 — Shift Window (Location-timezone aware).** IF server time (stored UTC, compared in the Location's timezone) is outside `[shift_start, shift_end]` on `assignment_date` for the resolved assignment, THEN THE Officer_API SHALL return HTTP 403 with reason code `OUTSIDE_SHIFT_WINDOW` and SHALL NOT write an attendance record. The response body SHALL include `bypass_eligible: true`.
6. **Step 4 — Mock Location Detection.** IF `mock_location = true` in the request payload, THEN THE Officer_API SHALL return HTTP 403 with reason code `MOCK_LOCATION_DETECTED`, SHALL set `bypass_eligible: false` in the response body, SHALL NOT write an attendance record, and SHALL write an audit event `CHECKIN_REJECTED` with reason `MOCK_LOCATION_DETECTED`.
7. **Step 5 — Geofence Validation.** WHEN `mock_location = false`, THE GeofenceService SHALL evaluate `ST_DWithin(POINT(longitude, latitude), location.coordinates, location.radius_meters)` in SRID 4326. IF the result is false, THEN THE Officer_API SHALL return HTTP 422 with reason code `OUTSIDE_GEOFENCE`, a `distance_meters` field, and `bypass_eligible: true`.
8. **Step 6 — GPS Accuracy Flag.** WHEN `gps_accuracy > 50`, THE ProcessCheckinAction SHALL NOT reject the check-in but SHALL add `LOW_GPS_ACCURACY` to the eventual attendance's `spoofing_signals` JSONB and set `status = 'flagged'` on the attendance.
9. **Step 7 — Timestamp Drift Flag.** WHEN `|server_now - timestamp_device| > config.policehazard.spoofing.timestamp_drift_seconds`, THE ProcessCheckinAction SHALL add `TIMESTAMP_DRIFT` to `spoofing_signals` with a `+1` contribution to `spoofing_score` but SHALL NOT reject.
10. **Step 8 — Spoofing Multi-Signal Analysis.** THE SpoofingDetectionService SHALL compute `spoofing_score` and populate `spoofing_signals` per PRD §13.2 signals. IF `spoofing_score >= config.policehazard.spoofing.auto_reject_score`, THEN THE Officer_API SHALL return HTTP 422 with reason code `SPOOFING_REJECTED`, a `spoofing_signals` summary, `bypass_eligible: true`, and SHALL write an audit event `CHECKIN_REJECTED`.
11. **Step 9 — Duplicate Guard (PH only).** IF `operation.operation_type = 'PH'` and a verified attendance already exists for the resolved `assignment_id`, THEN THE Officer_API SHALL return HTTP 409 with reason code `CHECKIN_ALREADY_COMPLETED`, `bypass_eligible: false`, and SHALL NOT write a new attendance record.
12. **Step 10 — Photo Validation.** THE ProcessCheckinAction SHALL validate the uploaded `photo` by inspecting the first 12 bytes (magic bytes) against the JPEG (`FF D8 FF`) and PNG (`89 50 4E 47 0D 0A 1A 0A`) signatures; IF bytes do not match, THEN THE Officer_API SHALL return HTTP 422 with reason code `PHOTO_INVALID`. THE ProcessCheckinAction SHALL reject photos larger than `config.policehazard.photo.max_size_mb` with reason code `PHOTO_TOO_LARGE`.
13. **Step 11 — Atomic Write.** THE ProcessCheckinAction SHALL insert the attendance record inside a single DB transaction containing `distance_from_point`, `is_within_geofence`, `checkin_coordinates` (PostGIS `POINT(lng, lat)` SRID 4326), `checked_in_at = now()`, `shift_window_start`/`shift_window_end` snapshots in UTC, `is_within_shift`, `is_manual_bypass = false`, `bypass_approval_id = null`, `spoofing_score`, `spoofing_signals`, `device_metadata` (full request metadata JSONB), `photo_raw_path` (local disk path), `photo_status = 'pending'`, `photo_path = null`, `status = 'verified'` (or `'flagged'` when any signal triggered), and a **server-computed** `checksum = SHA-256(id || assignment_id || officer_id || location_id || checkin_coordinates || checked_in_at || is_within_geofence || is_within_shift || spoofing_score)` — computed by the server at INSERT time using the attendance row's own fields. There is no client-submitted checksum.
14. **Step 11b — Post-Commit Job + Cache.** WHEN the DB transaction of Step 11 commits, THE ProcessCheckinAction SHALL dispatch the `ProcessCheckinPhoto` queued job (Horizon) and SHALL invoke `DashboardCacheInvalidator::invalidateFor()` — both registered via `DB::afterCommit` so a rolled-back transaction never enqueues a job or evicts cache.
15. **Step 12 — Response.** WHEN all prior steps pass, THE Officer_API SHALL return HTTP 200 with `{ status: "success", attendance_id, checked_in_at (ISO 8601 with UTC offset), distance_from_point, is_flagged, photo_status }`.
16. IF the authenticated officer submits more than `config.policehazard.auth.checkin_rate_limit` check-in attempts within 60 seconds, THEN THE Officer_API SHALL return HTTP 429 with reason code `RATE_LIMITED` and `retry_after_seconds`.
17. WHEN `POST /api/v1/officer/checkin` is received, THE Officer_API SHALL write an audit event `CHECKIN_ATTEMPT` via AuditService including `actor_id`, `assignment_id`, `latitude`, `longitude`, `gps_accuracy`, `mock_location`, `actor_ip`, regardless of validation outcome.
18. WHEN `POST /api/v1/officer/checkin` succeeds, THE Officer_API SHALL write an audit event `CHECKIN_VERIFIED` including `attendance_id`, `distance_from_point`, `spoofing_score`, `status`.
19. WHEN `POST /api/v1/officer/checkin` is rejected at any step other than Step 1, THE Officer_API SHALL write an audit event `CHECKIN_REJECTED` including the `reason_code` and relevant step-specific fields.
20. THE Officer_API SHALL strip EXIF metadata from the stored photo before persisting `photo_raw_path` and SHALL replace any original filename with the attendance UUID.
21. THE Officer_API SHALL store photos outside the public webroot and SHALL serve them only via presigned URLs whose TTL is at most 15 minutes.

### Requirement 4: Manual Bypass Request Workflow (No Token)

**User Story:** As an Officer, when my check-in legitimately fails for a bypass-eligible reason, I want to submit the same GPS + photo + a note for supervisor review, so a temporary GPS or timing failure does not incorrectly mark me absent.

#### Acceptance Criteria

1. WHEN `POST /api/v1/officer/checkin` is rejected with reason code `OUTSIDE_SHIFT_WINDOW`, `OUTSIDE_GEOFENCE`, or `SPOOFING_REJECTED`, THE Officer_API SHALL include `bypass_eligible: true` in the error response body; for any other rejection it SHALL include `bypass_eligible: false` (PRD §5.4, with the documented extension to `SPOOFING_REJECTED`).
2. THE Officer_API SHALL expose `POST /api/v1/officer/bypass-request` accepting `Content-Type: multipart/form-data` with fields `assignment_id` (uuid), `reason_code` (string — must be one of the three bypass-eligible codes), `latitude`, `longitude`, `gps_accuracy`, `gps_altitude` (optional), `gps_speed` (optional), `gps_provider`, `timestamp_device`, `mock_location` (boolean, must be false), `photo` (file), `officer_note` (string, minimum 20 characters).
3. IF `mock_location = true` is submitted on `POST /api/v1/officer/bypass-request`, THEN THE Officer_API SHALL return HTTP 403 with reason code `MOCK_LOCATION_NEVER_BYPASSABLE` — defense-in-depth mirroring R5.15.
4. IF `reason_code` is not one of `OUTSIDE_SHIFT_WINDOW`, `OUTSIDE_GEOFENCE`, `SPOOFING_REJECTED`, THEN THE Officer_API SHALL return HTTP 422 with reason code `REASON_CODE_NOT_BYPASS_ELIGIBLE`.
5. IF `officer_note` is shorter than 20 characters or empty, THEN THE Officer_API SHALL return HTTP 422 with reason code `OFFICER_NOTE_REQUIRED`.
6. IF `photo` is missing or fails magic-byte validation (R3.12), THEN THE Officer_API SHALL return HTTP 422 with reason code `BYPASS_PHOTO_MISSING` or `PHOTO_INVALID` respectively.
7. WHEN validation passes, THE Officer_API SHALL resolve the assignment the same way Step 2 of the check-in pipeline does; IF the assignment is not found / cross-tenant / cancelled, THEN THE Officer_API SHALL return HTTP 422 with reason code `ASSIGNMENT_NOT_FOUND`.
8. WHEN the assignment resolves and the operation is `PH` AND a verified attendance already exists for it, THEN THE Officer_API SHALL return HTTP 409 with reason code `CHECKIN_ALREADY_COMPLETED` (an officer cannot bypass after they already succeeded).
9. WHEN validation passes, THE Officer_API SHALL persist a `manual_bypass_approvals` row with: `status = 'pending'`, `assignment_id`, `officer_id`, `saker_id`, `bypass_reason = reason_code`, `officer_note`, `officer_latitude`, `officer_longitude`, `officer_gps_accuracy`, `officer_gps_altitude`, `officer_gps_speed`, `officer_gps_provider`, `officer_photo_path` (stored on private disk, EXIF-stripped, UUID-named), `officer_device_metadata` (JSONB of full request metadata), `officer_timestamp_device`, `expires_at = now() + config.policehazard.bypass.{ph|patrol}_ttl_minutes` based on operation type, `created_at = now()`.
10. WHEN a bypass request is created, THE Officer_API SHALL write an audit event `MANUAL_BYPASS_REQUESTED` including `actor_id`, `assignment_id`, `reason_code`, `manual_bypass_approval_id`.
11. WHEN a bypass request is created, THE NotificationService SHALL create `BYPASS_REQUEST` notifications for all Saker Admins of the officer's Saker plus any God Admin.
12. THE Officer_API SHALL expose `GET /api/v1/officer/bypass-request/{id}` which returns `{ id, status, bypass_reason, officer_note, reviewer_note, expires_at, created_at, reviewed_at, attendance_id (when approved) }` for a record owned by the authenticated officer; otherwise HTTP 404.
13. WHEN a bypass request's `expires_at` is reached without a decision, THE scheduler SHALL transition `status` to `expired`, SHALL write `MANUAL_BYPASS_EXPIRED` to audit_logs, SHALL notify the officer with `BYPASS_EXPIRED`, and SHALL NOT create an attendance record.
14. WHEN a bypass request has been in `status = 'pending'` for `config.policehazard.escalation.god_admin_after_minutes` minutes, THE NotificationService SHALL deliver a `BYPASS_REQUEST` notification to all God Admins.
15. WHEN a bypass request has been in `status = 'pending'` for `config.policehazard.escalation.email_after_minutes` minutes, THE NotificationService SHALL send an email to all Saker Admins of the officer's Saker.
16. THE escalation + expiration scheduler SHALL run every minute via Laravel's task scheduler and SHALL NOT use blocking delays.
17. THE Officer_API SHALL enforce a rate limit of `config.policehazard.auth.bypass_rate_limit` bypass requests per authenticated officer per 60 seconds, keyed by officer_id.

### Requirement 5: Supervisor Reviews and Decides Bypass Requests

**User Story:** As a Saker Admin (Supervisor), I want to see pending bypass requests in the admin web interface, compare the officer's submitted GPS to the location GPS on a map, view the submitted photo, and approve or deny with a mandatory note — so bypass decisions are deliberate and auditable.

#### Acceptance Criteria

1. THE Admin_Web_Interface SHALL provide `GET /bypass-approvals` under the existing `['auth', 'god.admin']` middleware group that lists bypass requests in the supervisor's scope (God Admin sees all; Saker Admin sees only their Saker's).
2. THE bypass-approvals index SHALL paginate results, default to `status = 'pending'`, and support filtering by `status`, `bypass_reason`, `date_range`, and `officer_name`.
3. THE Admin_Web_Interface SHALL provide `GET /bypass-approvals/{id}` showing officer name, NRP, assignment details, shift window (in the Location's timezone, rendered as `DD-MM-YYYY HH:MM WIB|WITA|WIT`), `bypass_reason`, `officer_note`, a Leaflet comparison map showing both the location coordinates (with geofence circle) and the officer's submitted coordinates, the numeric `distance_meters` delta, and the officer's submitted photo. WHERE `bypass_reason = 'SPOOFING_REJECTED'`, the page SHALL additionally display the full `officer_device_metadata` + suspicious signals with a red advisory banner `VERIFIKASI SINYAL SPOOFING SEBELUM PERSETUJUAN`.
4. THE Admin_Web_Interface SHALL provide `POST /bypass-approvals/{id}/approve` accepting `reviewer_note` (minimum 20 characters). WHEN the target record has `status = 'pending'` and `expires_at > now()`, the endpoint SHALL transition it to `status = 'approved'`, stamp `reviewed_by = auth().user().id`, `reviewed_at = now()`, persist `reviewer_note`, and create an attendance record from the stored officer GPS + photo + timestamp fields with `is_manual_bypass = true`, `bypass_approval_id = {id}` — all inside a single DB transaction.
5. WHEN the approve endpoint succeeds, THE ApproveManualBypassAction SHALL dispatch `ProcessCheckinPhoto` post-commit and SHALL invoke `DashboardCacheInvalidator::invalidateFor()` post-commit (same cache keys as R3.14).
6. THE Admin_Web_Interface SHALL provide `POST /bypass-approvals/{id}/deny` accepting `reviewer_note` (minimum 20 characters). WHEN the target record has `status = 'pending'` and `expires_at > now()`, the endpoint SHALL transition it to `status = 'denied'`, stamp `reviewed_by`, `reviewed_at`, and persist `reviewer_note` — inside a single DB transaction. It SHALL NOT create an attendance record.
7. IF the target bypass record is not in `status = 'pending'`, THEN the approve and deny endpoints SHALL return HTTP 409 with message `BYPASS_DECISION_ALREADY_MADE`.
8. IF the target bypass record's `expires_at < now()`, THEN the approve and deny endpoints SHALL return HTTP 410 with message `BYPASS_EXPIRED` and SHALL transition the record to `status = 'expired'` as part of the same handler.
9. IF `reviewer_note` is shorter than 20 characters, THEN the approve and deny endpoints SHALL return HTTP 422 with reason code `SUPERVISOR_NOTE_REQUIRED`.
10. IF the supervisor attempts to approve or deny a bypass whose `saker_id` differs from the supervisor's `saker_id` AND the supervisor is not a God Admin, THEN the endpoints SHALL return HTTP 403 and write an audit event `BYPASS_CROSS_TENANT_ATTEMPT`.
11. WHEN approve succeeds, THE AuditService SHALL write `MANUAL_BYPASS_APPROVED` containing `manual_bypass_approval_id`, `reviewed_by`, `reviewer_note`, and the resulting `attendance_id`.
12. WHEN deny succeeds, THE AuditService SHALL write `MANUAL_BYPASS_DENIED` containing `manual_bypass_approval_id`, `reviewed_by`, `reviewer_note`.
13. WHEN approve or deny succeeds, THE NotificationService SHALL create a `BYPASS_APPROVED` or `BYPASS_DENIED` notification for the requesting officer including a reference to `attendance_id` on approval or `reviewer_note` on denial.
14. THE bypass-approvals pages SHALL be rendered under `resources/views/bypass-approvals/` and controlled by a new `BypassApprovalController` under `app/Http/Controllers/`.
15. THE approve and deny endpoints SHALL reject any action on a record with `bypass_reason = 'MOCK_LOCATION_DETECTED'` with HTTP 422 and message `MOCK_LOCATION_NEVER_BYPASSABLE`, as a defense-in-depth check even though R4.3 prevents such requests from being created.

### Requirement 6: Officer Views Attendance History

**User Story:** As an Officer, I want to review my own past check-ins, so I can confirm my attendance has been recorded correctly.

#### Acceptance Criteria

1. THE Officer_API SHALL expose `GET /api/v1/officer/attendance/history` accepting `from` (date), `to` (date), `page` (integer) query parameters; defaulting `from = today - 30 days`, `to = today`, `page = 1`.
2. THE Officer_API SHALL return a paginated JSON response with `data`, `current_page`, `per_page = 20`, `total`, `last_page`, containing only the authenticated officer's attendances.
3. THE Officer_API SHALL return, per attendance: `attendance_id`, `assignment_id`, `location_name`, `location_timezone`, `checked_in_at` (ISO 8601 with UTC offset), `distance_from_point`, `status` (`verified`, `flagged`, `rejected`), `is_manual_bypass`, `photo_status`, and `photo_url` (presigned, TTL ≤ 15 minutes) when `photo_status = 'processed'`.
4. THE Officer_API SHALL expose `GET /api/v1/officer/attendance/{id}` which returns the full attendance record for a single record owned by the authenticated officer, or HTTP 404.
5. THE attendance history endpoints SHALL never return attendances whose `officer_id` is not the authenticated officer, nor attendances whose `saker_id` does not match the authenticated officer's `saker_id`.

### Requirement 7: Mobile Web Officer UI

**User Story:** As an Officer, I want a phone-friendly dark-mode web UI matching the admin interface visually that guides me through login, assignments, check-in, bypass, and history — so I can complete my duties from any modern mobile browser without installing an app.

#### Acceptance Criteria

1. THE Mobile_Web_UI SHALL provide a login screen at `GET /officer/login` with fields `nrp` (numeric keyboard) and `password` (masked), posting to `POST /api/v1/auth/login` and storing the returned bearer token in `sessionStorage` (not `localStorage`).
2. THE Mobile_Web_UI SHALL NOT capture or transmit a device fingerprint on any request.
3. THE Mobile_Web_UI SHALL present a Today's Assignments screen at `GET /officer/assignments` showing officer name, NRP, Saker, avatar, a `± 7` day date switcher, and assignment cards sorted by `shift_start` with a status badge of `Pending`, `Attended`, `Not Attended`, or `Flagged`.
4. THE Mobile_Web_UI SHALL render an Assignment Detail screen at `GET /officer/assignments/{id}` with location name, address, a Leaflet mini-map with the location pin and a `radius_meters` circle, a live distance indicator that polls `Geolocation.watchPosition` at most every 5 seconds (green inside geofence, red outside, amber when `gps_accuracy > 30`).
5. WHEN the current time falls outside the assignment's shift window, or the officer has already verified-checked-in for a PH assignment, THE Mobile_Web_UI SHALL disable the CHECK-IN button and display a textual reason.
6. WHEN the officer taps CHECK-IN, THE Mobile_Web_UI SHALL acquire a single-shot GPS fix via `Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 30000 })`. IF the timeout fires, THEN the UI SHALL show an inline error `GPS_TIMEOUT` and SHALL NOT submit.
7. THE Mobile_Web_UI SHALL open the camera via `MediaDevices.getUserMedia({ video: { facingMode: 'user' } })` and display a photo preview for confirmation before submitting.
8. WHEN the officer confirms the photo, THE Mobile_Web_UI SHALL submit the multipart form to `POST /api/v1/officer/checkin` with all fields listed in R3.1. The client SHALL NOT compute or transmit any checksum. IF the client detects that a checksum field is about to be computed or added to the outgoing request (e.g. via a debug hook or build-time assertion), THEN THE Mobile_Web_UI SHALL block the form submission, display an inline error, and SHALL NOT retry until the violating field is removed.
9. WHEN the check-in response is HTTP 200, THE Mobile_Web_UI SHALL show a success screen with a green checkmark, `checked_in_at` (in the Location's timezone), `distance_from_point`, and the text `Absensi berhasil tercatat`.
10. WHEN the check-in response carries `bypass_eligible: true`, THE Mobile_Web_UI SHALL show the rejection reason, the delta (time, distance, or spoofing signal summary), and a prominent "Ajukan Bypass" action that navigates to the bypass screen carrying the in-memory GPS + photo bundle.
11. WHEN the check-in response carries `bypass_eligible: false`, THE Mobile_Web_UI SHALL NOT offer the "Ajukan Bypass" action; it SHALL display the rejection message, and for `MOCK_LOCATION_DETECTED` the banner SHALL be non-dismissable.
12. THE Mobile_Web_UI SHALL present a Bypass Request screen at `GET /officer/bypass` whose initial rendering contains a text area labeled `Keterangan` (`minlength=20`), a photo re-confirmation thumbnail, and a submit button posting to `POST /api/v1/officer/bypass-request`. The submit action and subsequent Pending status screen are governed by R7.12a and R7.12b; they are not required to be active simultaneously with the initial bypass request screen.
12a. WHEN the officer submits the Bypass Request form, THE Mobile_Web_UI SHALL replace the initial screen with a Pending status screen that auto-polls `GET /api/v1/officer/bypass-request/{id}` every 30 seconds.
13. WHEN a bypass becomes `approved`, THE Mobile_Web_UI SHALL transition the Pending screen to a success animation and show the `attendance_id`.
14. WHEN a bypass becomes `denied` or `expired`, IF a `reviewer_note` is present, THEN the Mobile_Web_UI SHALL display both the `reviewer_note` and the final status together; OTHERWISE THE Mobile_Web_UI SHALL display only the final status. On dismissal, THE Mobile_Web_UI SHALL return the officer to the Assignment Detail screen.
15. THE Mobile_Web_UI SHALL provide an Attendance History screen at `GET /officer/history` that paginates `GET /api/v1/officer/attendance/history` and opens a detail view per item showing the watermarked photo when `photo_status = 'processed'`.
16. THE Mobile_Web_UI SHALL use the same Tailwind dark-mode palette as the existing admin interface (`resources/views/layouts/admin.blade.php`) and SHALL be laid out phone-first.
17. WHERE the environment is production, THE Mobile_Web_UI SHALL require HTTPS; on plain HTTP it SHALL display an inline error and SHALL NOT attempt camera or geolocation access (browsers block both on insecure origins anyway, but we surface the reason).
18. WHEN any API call returns HTTP 401 with `TOKEN_INVALID`, THE Mobile_Web_UI SHALL clear the `sessionStorage` token and redirect the officer to the login screen.
19. THE Mobile_Web_UI SHALL render all officer-facing timestamps in the Location's timezone as `DD-MM-YYYY HH:MM:SS WIB|WITA|WIT`. IF `Intl.DateTimeFormat` fails (missing timezone data), THEN the UI SHALL fall back to the device's locale formatting for that single timestamp and log the fallback to the browser console.

### Requirement 8: Audit Trail of Officer-Facing Events

**User Story:** As an auditor, I want every officer-facing event written immutably to `audit_logs`, so attendance decisions remain legally defensible.

#### Acceptance Criteria

1. THE AuditService SHALL write one row to `audit_logs` for each of: `OFFICER_LOGIN_SUCCESS`, `OFFICER_LOGIN_FAILED`, `CHECKIN_ATTEMPT`, `CHECKIN_VERIFIED`, `CHECKIN_REJECTED`, `MANUAL_BYPASS_REQUESTED`, `MANUAL_BYPASS_APPROVED`, `MANUAL_BYPASS_DENIED`, `MANUAL_BYPASS_EXPIRED`, `BYPASS_CROSS_TENANT_ATTEMPT`, `PHOTO_WATERMARK_FAILED`.
2. THE AuditService SHALL populate `actor_id`, `actor_ip`, `actor_user_agent`, `saker_id`, `event_type`, `entity_type`, `entity_id`, `payload_before`, `payload_after`, `metadata` per Phase 1 schema.
3. THE AuditService SHALL NOT include raw passwords, bearer tokens, or password hashes in any field. It SHALL redact any metadata key matching `password`, `authorization`, `bearer`, `token`, `secret` before INSERT.
4. THE AuditService SHALL rely on the Phase 1 database-level `no_update_audit_logs` and `no_delete_audit_logs` rules as the final backstop; application code SHALL never issue UPDATE or DELETE against `audit_logs`.
5. THE AuditService SHALL write the audit row before returning a response to the client for every rejection and approval event; a failed audit write SHALL cause the operation to return HTTP 500 and SHALL NOT leave an inconsistent attendance or bypass record. WHEN the audit write succeeds for a rejection or approval event, THE system SHALL deliver the corresponding client response.

### Requirement 9: Cache Invalidation on Successful Check-In

**User Story:** As a Saker Admin watching the dashboard, I want officer check-ins to refresh the map and summary cards within the platform's freshness target.

#### Acceptance Criteria

1. WHEN a verified attendance record is written (via R3 or R5.4), THE DashboardCacheInvalidator SHALL delete Redis keys matching `dashboard:map-data:*` (coarse invalidation is acceptable for the existing `DashboardController@mapData` endpoint, which is not yet keyed by operation_id).
2. THE cache invalidation SHALL be registered via `DB::afterCommit` so a rolled-back transaction never evicts cache.
3. THE cache invalidation SHALL complete within 1 second at P95 and SHALL NOT block the HTTP response.
4. WHEN invalidation fails, THE action SHALL attempt to log the failure at `warning` level and SHALL NOT return an error to the client.
5. IF the `warning`-level log write itself fails, THEN the action SHALL continue silently and SHALL STILL NOT return an error.

### Requirement 10: Performance & Scalability Non-Functionals

**User Story:** As an operator, I want the officer API to sustain published concurrency and latency targets during shift-start peaks.

#### Acceptance Criteria

1. WHILE concurrent in-flight `POST /api/v1/officer/checkin` requests number 500 or fewer, THE Officer_API SHALL respond to `POST /api/v1/officer/checkin` within 2000ms at P95, excluding the queued photo watermark job. Above 500 concurrent requests, the 2000ms target does not apply.
2. WHILE concurrent in-flight `GET /api/v1/officer/assignments` requests number 500 or fewer, THE Officer_API SHALL respond to `GET /api/v1/officer/assignments` within 300ms at P95. Above 500 concurrent requests, the 300ms target does not apply.
3. THE GeofenceService SHALL satisfy `ST_DWithin` queries within 50ms at P95, backed by `idx_locations_coordinates` GIST.
4. THE Officer_API SHALL enforce `config.policehazard.auth.checkin_rate_limit` check-in attempts per officer per 60s.
5. THE Officer_API SHALL enforce `config.policehazard.auth.max_login_attempts` login attempts per NRP-or-IP per `config.policehazard.auth.lockout_minutes`.
6. THE ProcessCheckinPhoto job SHALL be attempted at least once regardless of `watermark_retry`; retries SHALL run with exponential backoff up to `config.policehazard.photo.watermark_retry` additional attempts before marking `photo_status = 'failed'`. WHERE `watermark_retry = 0`, success on the first attempt SHALL mark `photo_status = 'processed'` and failure SHALL mark `photo_status = 'failed'` immediately.
7. THE Officer_API SHALL set `Cache-Control: no-store` on all authenticated JSON responses.

### Requirement 11: Security Non-Functionals (Simplified)

**User Story:** As a security reviewer, I want Phase 3 to hold every Phase 1–2 security property without adding unnecessary cryptography.

#### Acceptance Criteria

1. THE Officer_API SHALL validate uploaded photos by magic-byte inspection, independent of `Content-Type` or filename.
2. THE Officer_API SHALL enforce `config.policehazard.photo.max_size_mb` at both the web server (via Nginx `client_max_body_size`) and the application level.
3. THE Officer_API SHALL strip EXIF metadata and rename photos to the attendance or bypass UUID before storage.
4. THE Officer_API SHALL return all responses with HTTP security headers per PRD §13.4: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Strict-Transport-Security: max-age=31536000; includeSubDomains` (production only), `Permissions-Policy: geolocation=(self), camera=(self)`.
5. THE Officer_API SHALL never log raw passwords, raw bearer tokens, or password hashes at any log level.
6. THE Officer_API SHALL require `EnsureSakerContext` middleware on every `auth:sanctum` route. THE application SHALL fail fast at `AppServiceProvider::boot()` in non-production environments by scanning **every registered route regardless of whether it uses the `auth:sanctum` guard**: IF any Sanctum-guarded route (or any route the scanner inspects) is registered without `EnsureSakerContext`, the application SHALL throw a startup-time `DomainException`. In production, the same route scan SHALL run; IF a Sanctum-guarded route lacks `EnsureSakerContext`, THE application SHALL log a `critical` warning AND THE Officer_API SHALL reject any incoming request that hits such a route with HTTP 500 and reason code `MIDDLEWARE_MISCONFIGURED`.
7. THE `attendances.checksum` column SHALL be populated by the server at INSERT time using `SHA-256(id || assignment_id || officer_id || location_id || ST_AsBinary(checkin_coordinates) || checked_in_at::text || is_within_geofence::text || is_within_shift::text || spoofing_score::text)`. There is no client-submitted checksum. The column's purpose is tamper detection inside the DB; it is not a signed artifact.

### Requirement 12: Location-Scoped Timezone

**User Story:** As a Saker Admin deploying across WIB, WITA, and WIT, I want each Location to carry its own timezone so shift-window validation and officer-facing displays match the field reality.

#### Acceptance Criteria

1. THE locations table SHALL gain a `timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Jakarta'` column constrained to the set `('Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura')` — Indonesia's three civil timezones.
2. THE Phase 3 seeder SHALL backfill `timezone` for every existing Location row based on its longitude: `< 115` → `Asia/Jakarta` (WIB); `115` ≤ `lng` < `135` → `Asia/Makassar` (WITA); `>= 135` → `Asia/Jayapura` (WIT).
3. THE ProcessCheckinAction SHALL compute `shift_window_start` and `shift_window_end` in the Location's `timezone` relative to the resolved `assignment.assignment_date`, then persist both as `TIMESTAMPTZ` in UTC.
4. WHERE `shift.shift_end < shift.shift_start` (midnight-spanning), THE ProcessCheckinAction SHALL construct `shift_window_start = assignment_date + shift.shift_start` (in Location timezone) and `shift_window_end = (assignment_date + 1 day) + shift.shift_end` (in Location timezone) and SHALL attribute the attendance to `assignment_date` — the date of `shift_start`. WHERE `shift.shift_end >= shift.shift_start`, these midnight-spanning constructions SHALL NOT be applied.
5. THE `GET /api/v1/officer/assignments` response SHALL include `location_timezone` per assignment (R2.6).
6. THE Supervisor Bypass Queue detail page SHALL render all times in the Location's timezone with a `WIB|WITA|WIT` suffix.
7. THE Mobile_Web_UI SHALL render all officer-facing timestamps in the Location's timezone (R7.19).

### Requirement 13: Database Seeder Split

**User Story:** As a developer, I want the monolithic `DatabaseSeeder.php` split into ten focused seeders so I can reseed a single domain without re-running the whole thing.

#### Acceptance Criteria

1. THE `database/seeders/` directory SHALL contain exactly these files after Phase 3 (in addition to any existing `DatabaseSeeder.php` which becomes the orchestrator):
   - `SakersSeeder.php` — creates the POLDA → POLRESTABES → POLSEK hierarchy.
   - `GodAdminSeeder.php` — creates the single `god_admin` user.
   - `SakerAdminsSeeder.php` — creates one `saker_admin` per Saker.
   - `OfficersSeeder.php` — creates 10 `officer` users per Saker.
   - `OperationsSeeder.php` — creates the four sample operations.
   - `ZonesSeeder.php` — creates two zones per operation.
   - `LocationsSeeder.php` — creates the 15 geospatial sample locations and backfills `timezone` per R12.2.
   - `ShiftsSeeder.php` — creates two shifts per location (Pagi + Sore).
   - `AssignmentsSeeder.php` — creates today + yesterday assignments.
   - `AttendancesSeeder.php` — creates sample yesterday attendances (dev demo only).
2. THE `DatabaseSeeder` class SHALL orchestrate by calling `$this->call([SakersSeeder::class, GodAdminSeeder::class, ..., AttendancesSeeder::class])` in the listed order.
3. Each seeder SHALL be idempotent (re-runnable) by using `firstOrCreate` or unique-code lookups rather than relying on empty-table preconditions.
4. Each seeder SHALL look up dependencies by unique code (e.g. `Saker::where('code', 'POLDA-JATENG')->sole()`) rather than hard-coding UUIDs or depending on call-order-specific state.
5. THE existing `REFRESH MATERIALIZED VIEW daily_attendance_summary` statement SHALL remain in `DatabaseSeeder::run()` as the final step after all entity seeders.
6. `php artisan migrate:fresh --seed` SHALL continue to produce the same data counts as Phase 1–2 (3 Sakers, 34 Users, 4 Ops, 8 Zones, 15 Locations, 30 Shifts, plus assignments and attendances).

## Correctness Properties (for Property-Based Testing)

These invariants are the contract the implementation must hold under property-based tests. Infrastructure-heavy properties (Redis, S3) are covered by integration tests, not PBT.

### P1 — Attendance Immutability
For every `attendances` row `A` and every application code path `F`: after `F`, `SELECT * FROM attendances WHERE id = A.id` returns a tuple deep-equal to `A` except possibly `photo_path` and `photo_status` (which may transition `pending → processed|failed` at most once). Enforces R3.13, R3.14, R8.4.

### P2 — One Verified Check-In Per PH Assignment
For every PH `Assignment` `X` and any sequence (incl. concurrent) of check-in calls referencing `X`: `COUNT(*) FROM attendances WHERE assignment_id = X.id AND status = 'verified' AND is_manual_bypass = false` ≤ 1. Enforces R3.11.

### P3 — Mock Location Never Produces Attendance
For every check-in or bypass request `R` with `mock_location = true`: no `attendances` row is created that would not exist without `R`. Enforces R3.6, R4.3.

### P4 — Cross-Tenant Isolation
For every officer `O` with `saker_id = S_A` and every Location/Assignment pair owned by `saker_id = S_B ≠ S_A`: any `POST /api/v1/officer/checkin` or `POST /api/v1/officer/bypass-request` referencing the foreign assignment returns `ASSIGNMENT_NOT_FOUND`, and no row in `attendances` or `manual_bypass_approvals` with `saker_id = S_B` is created that references `officer_id = O.id`. Enforces R2.11, R3.4, R5.10, R11.6.

### P5 — Audit Log Append-Only
For every sequence of operations and every `audit_logs` row inserted by any of them: no subsequent operation produces a byte-different row with the same `id`. Enforces R8.4.

### P6 — Midnight-Spanning Shift Attribution
For every assignment `A` whose shift has `shift_end < shift_start`: any successful check-in whose `checked_in_at` lies in `[A.assignment_date + shift.shift_start, (A.assignment_date + 1 day) + shift.shift_end]` in the Location's timezone yields an `attendances` row linked to an assignment attributed to `A.assignment_date`, not `A.assignment_date + 1`. Enforces R12.3, R12.4.

### P7 — Bypass Approval ↔ Attendance Linkage Monotonicity
For every `manual_bypass_approvals` row `B` with `status = 'approved'`: exactly one `attendances` row `A` exists with `A.bypass_approval_id = B.id`, `A.is_manual_bypass = true`, `A.assignment_id = B.assignment_id`. For `B.status ∈ {pending, denied, expired}`: zero such rows exist. Enforces R5.4, R5.6.

### P8 — Reason Code ↔ Bypass Eligibility Contract
For every check-in rejection response `E` with `reason_code ∈ {OUTSIDE_SHIFT_WINDOW, OUTSIDE_GEOFENCE, SPOOFING_REJECTED}`: `E.bypass_eligible = true`. For every rejection with any other reason code: `E.bypass_eligible = false`. Enforces R4.1.

### P9 — Attendance Record Completeness
For every `attendances` row written by R3 or R5.4: `checkin_coordinates`, `distance_from_point`, `is_within_geofence`, `checked_in_at`, `shift_window_start`, `shift_window_end`, `is_within_shift`, `spoofing_score`, `device_metadata`, `checksum`, and `saker_id` are non-null. Enforces R3.13.

### P10 — Tenant-Scoped Assignment Visibility
For every officer `O`: `GET /api/v1/officer/assignments` returns only rows where `assignment.officer_id = O.id` AND `assignment.saker_id = O.saker_id` AND `assignment.status != 'cancelled'`. Enforces R2.5, R2.11.

---

**References.** PRD citations refer to `Police_Hazard_PRD_v2.1.md`. Config keys reference `config/policehazard.php`. Pre-existing services (`AuditService`, `GeofenceService`, `SpoofingDetectionService`, `WatermarkService`, `NotificationService`) and pre-existing middleware (`EnsureSakerContext`, `SetGodAdminContext`) are from Phases 1–2 and are used unchanged except where an additive method surface is noted in `design.md`.
