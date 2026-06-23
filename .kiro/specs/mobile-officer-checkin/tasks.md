# Implementation Plan — Phase 3: Mobile Officer Check-In

Ordered bottom-up (schema → config → services → actions → HTTP → UI → scheduler → tests → docs → release). Each task cites the Requirement IDs it satisfies. Nothing in this list modifies Phase 1–2 code except the three narrow migrations in §2 and the single tagged-cache tweak in §11.1.

## 0. Preflight

- [x] 0.1 Create branch `feat/phase-3-mobile-officer-checkin`.
- [x] 0.2 Add `giorgiosironi/eris:^1.0` to `composer.json` as a dev dependency. (JWT lib is not needed — bypass tokens dropped.)
- [x] 0.3 `composer install`.
- [x] 0.4 `composer test` — confirm Phase 1–2 suite still green before any changes.

## 1. Configuration

- [x] 1.1 Edit `config/policehazard.php` per design §16:
  - Add `auth.bypass_rate_limit`, `auth.token_expiry_hours` if missing, confirm `auth.checkin_rate_limit`.
  - Add `photo.s3_disk`, `photo.presigned_ttl_min`, `photo.private_disk`, `photo.private_path`.
  - Add `default_timezone`. _(R1.6, R10.4, R10.6, R11.2, R12.1)_
- [x] 1.2 Update `.env.example` with the matching `PH_*` keys.
- [x] 1.3 Ensure `config/sanctum.php` does not globally expire tokens (we control per-token `expires_at`).

## 2. Database — three narrow migrations

- [x] 2.1 Create `database/migrations/2026_05_12_000001_add_timezone_to_locations.php` (design §9.1). Backfill longitude → timezone as part of the migration. _(R12.1, R12.2)_
- [x] 2.2 Create `database/migrations/2026_05_12_000002_extend_manual_bypass_approvals_for_phase3.php` (design §9.2): add the 10 officer-submitted GPS/photo/metadata/device columns + `escalation_level`, widen `chk_bypass_reason`, replace `no_update_manual_bypass` with the narrow-transition rule. _(R4.9, R4.14–R4.16, R5.4, R5.6)_
- [x] 2.3 Create `database/migrations/2026_05_12_000003_allow_narrow_photo_update_on_attendances.php` (design §9.3): replace `no_update_attendances` with the narrow `photo_path`/`photo_status` transition rule. _(R3.14, P1)_
- [x] 2.4 Write reversible `down()` for each migration that restores the original rules.
- [x] 2.5 Run `php artisan migrate` on local Postgres; verify each rule via `\d+ attendances` and `\d+ manual_bypass_approvals`.
- [x] 2.6 `tests/Feature/Migrations/Phase3MigrationTest.php` — insert pending bypass, transition to approved, attempt to modify immutable columns, assert no-op. Insert attendance, transition `photo_status` pending → processed, attempt to modify `distance_from_point`, assert no-op. Skip on non-Postgres.

## 3. Seeder split (10 files per R13)

- [x] 3.1 Create `database/seeders/SakersSeeder.php` with idempotent `firstOrCreate` lookups by `code`. _(R13.1, R13.3, R13.4)_
- [x] 3.2 Create `database/seeders/GodAdminSeeder.php`.
- [x] 3.3 Create `database/seeders/SakerAdminsSeeder.php`.
- [x] 3.4 Create `database/seeders/OfficersSeeder.php` (10 officers per Saker; look up Saker by `code`).
- [x] 3.5 Create `database/seeders/OperationsSeeder.php` — produces the four sample operations with the Phase 1 `start_time`/`end_time` column names (accepted as-is per earlier decision).
- [x] 3.6 Create `database/seeders/ZonesSeeder.php`.
- [x] 3.7 Create `database/seeders/LocationsSeeder.php` — use the existing PostGIS raw INSERT pattern; set `timezone` via the longitude-based rule from R12.2.
- [x] 3.8 Create `database/seeders/ShiftsSeeder.php`.
- [x] 3.9 Create `database/seeders/AssignmentsSeeder.php`.
- [x] 3.10 Create `database/seeders/AttendancesSeeder.php` — dev demo data only; move the sample-attendance INSERT block from the old monolithic seeder here.
- [x] 3.11 Rewrite `database/seeders/DatabaseSeeder.php` as orchestrator: `$this->call([SakersSeeder::class, GodAdminSeeder::class, SakerAdminsSeeder::class, OfficersSeeder::class, OperationsSeeder::class, ZonesSeeder::class, LocationsSeeder::class, ShiftsSeeder::class, AssignmentsSeeder::class, AttendancesSeeder::class]);` followed by `DB::statement('REFRESH MATERIALIZED VIEW daily_attendance_summary');`. _(R13.2, R13.5)_
- [x] 3.12 `php artisan migrate:fresh --seed` — confirm identical row counts to Phase 1–2 (3 Sakers, 34 Users, 4 Ops, 8 Zones, 15 Locations, 30 Shifts, plus assignments + attendances). _(R13.6)_
- [x] 3.13 Run each seeder twice in succession to prove idempotence (e.g., `php artisan db:seed --class=OfficersSeeder` twice without duplicate-NRP errors). _(R13.3)_

## 4. Middleware — security headers, Sanctum-aware saker context, fail-fast

- [x] 4.1 Create `app/Http/Middleware/SecurityHeadersMiddleware.php` per design §13. Generate `X-Request-ID` (UUID v7) and stash on request attributes. _(R1.13, R11.4, R10.7)_
- [x] 4.2 Register `SecurityHeadersMiddleware` globally for both `web` and `api` groups in `bootstrap/app.php`.
- [x] 4.3 Verify `EnsureSakerContext` resolves `saker_id` correctly when `auth:sanctum` is the guard; if not, extend it to read from `$request->user()?->saker_id`. Register alias `saker-context` if missing. _(R11.6)_
- [x] 4.4 Add the fail-fast assertion to `AppServiceProvider::boot()` per design §14. In non-prod, throws `DomainException` at boot; in prod, logs `critical` and records each flagged route URI into the container binding `ph.sanctum_routes_missing_saker_context`. _(R11.6)_
- [x] 4.4a Create `app/Http/Middleware/RejectMisconfiguredSanctumRoute.php` per design §14 — renders RFC 7807 `MIDDLEWARE_MISCONFIGURED` (HTTP 500) for any request whose matched route appears in the flagged list. Register as the first global middleware in the `api` group. _(R11.6)_
- [x] 4.5 Unit tests under `tests/Unit/Http/Middleware/`: header presence, env-conditional HSTS, Sanctum saker-context resolution, fail-fast raises in dev only, production branch logs critical AND `RejectMisconfiguredSanctumRoute` emits a 500 problem+json response for a flagged route.

## 5. Domain services — cache invalidator + timezone resolver

- [x] 5.1 Create `app/Services/DashboardCacheInvalidator.php` per design §10. Uses `Cache::tags(['dashboard'])->flush()` with double try/catch for log-down resilience. _(R9.1–R9.5)_
- [x] 5.2 Create `app/Services/LocationTimezoneResolver.php` with two methods: `shiftWindow(Shift $shift, Carbon $assignmentDate, string $timezone): array [start,end]` handling midnight-spanning; `tzAbbreviation(string $timezone): string` returning `WIB|WITA|WIT`. _(R12.3, R12.4, P6)_
- [x] 5.3 Unit tests for 5.1 (Redis-down and log-down paths) and 5.2 (all three timezones + midnight-spanning shift).

## 6. Repositories — extend for Phase 3 method surface

- [x] 6.1 Extend `AssignmentRepositoryInterface` + `AssignmentRepository`:
  - `findForOfficerToday(string $officerId, string $sakerId, ?Carbon $date = null): ?Assignment` (uses location timezone for "today").
  - `listForOfficer(string $officerId, string $sakerId, Carbon $from, Carbon $to): Collection`. _(R2)_
- [x] 6.2 Extend `AttendanceRepositoryInterface` + `AttendanceRepository`:
  - `verifiedExistsFor(string $assignmentId): bool`.
  - `insertVerified(CheckinDto $dto, string $checksum): Attendance` — with `lockForUpdate()` on assignment row for PH path.
  - `insertFromBypass(ManualBypassApproval $b): Attendance`.
  - `markPhotoProcessed(string $id, string $s3Key): void` (uses narrow rule from §2.3).
  - `markPhotoFailed(string $id): void`.
  - `listForOfficerHistory(string $officerId, Carbon $from, Carbon $to, int $page): LengthAwarePaginator`.
  - `findForOfficer(string $id, string $officerId): ?Attendance`.
  - `presignPhotoUrl(string $id): string`. _(R3, R5.5, R6)_
- [x] 6.3 Create `app/Repositories/Contracts/ManualBypassApprovalRepositoryInterface.php` + `app/Repositories/ManualBypassApprovalRepository.php`:
  - `createPending(array $attrs): ManualBypassApproval`.
  - `findPendingForUpdate(string $id): ManualBypassApproval` (uses `lockForUpdate`).
  - `markApproved(ManualBypassApproval $b, User $reviewer, string $note): void`.
  - `markDenied(ManualBypassApproval $b, User $reviewer, string $note): void`.
  - `markExpired(ManualBypassApproval $b): void`.
  - `advanceEscalation(ManualBypassApproval $b, int $level): void`.
  - `listForSupervisor(?string $sakerId, array $filters, int $page): LengthAwarePaginator`.
  - `listPendingAtEscalationLevel(int $level, int $afterMinutes): Collection`.
  - `listExpirable(): Collection`. _(R4, R5)_
- [x] 6.4 Register `ManualBypassApprovalRepositoryInterface` binding in `RepositoryServiceProvider`.
- [x] 6.5 Unit tests for each new method.

## 7. Actions — auth, check-in, bypass lifecycle, revocation

- [x] 7.1 `app/Actions/AuthenticateOfficerAction.php` per design §2.5. Writes `OFFICER_LOGIN_SUCCESS` / `OFFICER_LOGIN_FAILED` via `AuditService`. _(R1)_
- [x] 7.2 `app/Actions/ProcessCheckinAction.php` per design §3. Implements all 12 steps; uses `DB::transaction` + `DB::afterCommit` for post-commit side effects; throws typed `CheckinException` subclasses. Computes and stores server-internal checksum. _(R3, R9, R11.7, R12.3, P1, P2, P6, P9)_
- [x] 7.3 `app/Actions/CreateBypassRequestAction.php` per design §4.1. Validates reason_code, note length, photo magic-bytes; persists the officer bundle into `manual_bypass_approvals`. _(R4.2–R4.11, P3, P8)_
- [x] 7.4 `app/Actions/ApproveManualBypassAction.php` per design §5.1. Creates attendance from the bypass's stored officer bundle, dispatches photo job + cache invalidator post-commit. _(R5.4, R5.5, R5.7–R5.15, P7)_
- [x] 7.5 `app/Actions/DenyManualBypassAction.php` — transitions to denied, audits, notifies, no attendance created. _(R5.6–R5.13)_
- [x] 7.6 `app/Actions/ExpireBypassRequestsAction.php` — transitions pending rows past `expires_at` to expired, notifies officer. _(R4.13, R4.16)_
- [x] 7.7 `app/Actions/EscalateBypassRequestsAction.php` — advances `escalation_level` 0→1→2 with idempotent notifications. _(R4.14, R4.15, R4.16)_
- [x] 7.8 `app/Actions/RevokeOfficerTokensAction.php` — invoked when `users.is_active` flips false; calls `$user->tokens()->delete()`. _(R1.14)_
- [x] 7.9 Hook 7.8 into the existing `OfficerController@update` path so deactivation triggers revocation.
- [x] 7.10 DTOs/value objects under `app/Support/Dtos/`: `CheckinDto`, `BypassRequestDto`, `OfficerProfileDto`.
- [x] 7.11 Typed exception hierarchy under `app/Exceptions/Checkin/` and `app/Exceptions/Bypass/` with `reason_code`, `http_status`, `bypass_eligible`, `extra` fields per design §3.4.
- [x] 7.12 Unit tests `tests/Unit/Actions/*Test.php` — one per Action, cover every exception branch with mocked repos/services.

## 8. Queue job — photo watermarking

- [x] 8.1 Create `app/Jobs/ProcessCheckinPhoto.php` per design §7. Honor `watermark_retry` (including 0). _(R3.14, R10.6)_
- [x] 8.2 Confirm `WatermarkService` has a `watermark(Attendance $att): string` method that returns an S3 key; add if missing. EXIF stripping comes automatically from Intervention Image v4 re-encode. _(R3.20, R11.3)_
- [x] 8.3 On final failure, write `PHOTO_WATERMARK_FAILED` audit event and call `AttendanceRepository::markPhotoFailed`. _(R8.1)_
- [x] 8.4 Unit test `tests/Unit/Jobs/ProcessCheckinPhotoTest.php` — cover `watermark_retry = 0`, `1`, `3`; cover first-attempt success and exhaust-retries failure paths.

## 9. API controllers + routing

- [x] 9.1 `app/Http/Controllers/Api/V1/Officer/AuthController.php` — `login`, `logout`. _(R1)_
- [x] 9.2 `app/Http/Controllers/Api/V1/Officer/AssignmentController.php` — `index`, `show`, `distance`. _(R2)_
- [x] 9.3 `app/Http/Controllers/Api/V1/Officer/CheckinController.php` — `store`. _(R3)_
- [x] 9.4 `app/Http/Controllers/Api/V1/Officer/BypassRequestController.php` — `store`, `show`. _(R4)_
- [x] 9.5 `app/Http/Controllers/Api/V1/Officer/AttendanceHistoryController.php` — `index`, `show`. _(R6)_
- [x] 9.6 FormRequests under `app/Http/Requests/Api/V1/Officer/`:
  - `LoginRequest` — validates nrp, password, Content-Type.
  - `CheckinRequest` — validates all R3.1 fields including photo MIME + max size.
  - `BypassRequestRequest` — validates all R4.2 fields.
- [x] 9.7 Create `routes/api.php` with the route table from design §2.1.
- [x] 9.8 Register `routes/api.php` in `bootstrap/app.php` via `withRouting(api: __DIR__.'/../routes/api.php', apiPrefix: 'api')`.
- [x] 9.9 Create `app/Exceptions/ApiProblemRenderer.php` that maps `CheckinException`/`BypassException` to RFC 7807 responses with `reason_code`, `bypass_eligible`, `request_id`, and any `extra` fields. Hook it into `bootstrap/app.php` `withExceptions`. _(R1 error envelope, P8)_
- [x] 9.10 Feature tests `tests/Feature/Api/V1/` — one per endpoint covering every reason code.

## 10. Rate limiting

- [x] 10.1 In `AppServiceProvider::boot()` define `officer-login`, `officer-checkin`, `officer-bypass` rate limiters per design §12.
- [x] 10.2 Apply `throttle:officer-login` to `/api/v1/auth/login`, `throttle:officer-checkin` to `/api/v1/officer/checkin`, `throttle:officer-bypass` to `/api/v1/officer/bypass-request`.
- [x] 10.3 Custom `RATE_LIMITED` render path in the exception renderer (problem+json + `retry_after_seconds`).
- [x] 10.4 Feature tests that simulate burst calls and assert 429.

## 11. Dashboard cache tagging (the only Phase 1–2 touch)

- [x] 11.1 In `DashboardController@mapData`, wrap the existing response with `Cache::tags(['dashboard'])->remember(...)` keyed by `date`. No behavioral change — just enables tag-based eviction. Write a feature test confirming identical response shape before and after. _(R9.1)_

## 12. Supervisor bypass queue (admin web)

- [x] 12.1 Create `app/Http/Controllers/BypassApprovalController.php` — `index`, `show`, `approve`, `deny`. _(R5)_
- [x] 12.2 FormRequests: `app/Http/Requests/Admin/ApproveBypassRequest.php`, `DenyBypassRequest.php` — enforce `reviewer_note` ≥ 20 chars.
- [x] 12.3 Views under `resources/views/bypass-approvals/` per design §5.2: `index`, `show`, `_row`, `_decision-form`, `_spoofing-panel`.
- [x] 12.4 Register routes in existing `routes/web.php` `['auth','god.admin']` group: `Route::get('/bypass-approvals', ...)`, `Route::get('/bypass-approvals/{id}', ...)`, `Route::post('/bypass-approvals/{id}/approve', ...)`, `Route::post('/bypass-approvals/{id}/deny', ...)`.
- [x] 12.5 Add a sidebar nav item in `resources/views/layouts/admin.blade.php` visible to Saker Admins + God Admins, with an unread-count badge from pending bypass requests (reuse existing `NotificationService` patterns).
- [x] 12.6 Feature tests: `BypassApprovalIndexTest`, `BypassApprovalDecideTest`, `BypassApprovalCrossTenantTest`, `BypassApprovalExpiredTest`, `BypassApprovalMockLocationGuardTest`.

## 13. Mobile Web Officer UI

- [x] 13.1 Add mobile-web routes to `routes/web.php` (public, not behind `auth`): `/officer`, `/officer/login`, `/officer/assignments`, `/officer/assignments/{id}`, `/officer/checkin/{assignmentId}`, `/officer/bypass/{bypassId?}`, `/officer/history`.
- [x] 13.2 Create `resources/views/officer/layout.blade.php` — dark-mode Tailwind shell, HTTPS guard, mounts `officerApp` Alpine root, includes the officer JS bundle.
- [x] 13.3 Build the six screens (12.3 in design):
  - [x] 13.3.1 `officer/login.blade.php` — NRP + password form, posts to API, stores token + officer in `sessionStorage`. _(R7.1)_
  - [x] 13.3.2 `officer/assignments/index.blade.php` — today ± 7 day switcher, status badges. _(R7.3)_
  - [x] 13.3.3 `officer/assignments/show.blade.php` — detail + Leaflet mini-map + `watchPosition` distance indicator. _(R7.4, R7.5)_
  - [x] 13.3.4 `officer/checkin.blade.php` — state machine: acquire GPS → open camera → preview → submit. _(R7.6–R7.9)_
  - [x] 13.3.5 `officer/bypass.blade.php` — request form, pending poller, terminal screens. _(R7.10–R7.14)_
  - [x] 13.3.6 `officer/history/index.blade.php` + `officer/history/show.blade.php` — paginated list + photo lightbox. _(R7.15)_
- [x] 13.4 Alpine components under `resources/js/officer/`:
  - `officerApp.js` — root with token, API helper, logout, HTTPS guard.
  - `api.js` — fetch wrapper injecting Authorization Bearer.
  - `formatLocationTime.js` — timezone-aware formatter with device-locale fallback per R7.19.
  - `checkinScreen.js` — state machine implementation.
  - `bypassScreen.js` — submit + poller.
- [x] 13.5 Update `resources/js/app.js` to conditionally import officer modules on `/officer/*` URLs; add entry points in `vite.config.js` if needed.
- [x] 13.6 `npm run build` passes with zero errors.
- [x] 13.7 Feature test `tests/Feature/Web/OfficerUiRenderTest.php` — each route returns 200 with the expected Blade root and Alpine root selector present.

## 14. Scheduler

- [x] 14.1 Register both scheduled actions in `routes/console.php` per design §8 (`everyMinute`, `withoutOverlapping`, `runInBackground`). _(R4.13–R4.16)_
- [x] 14.2 Document in `README.md` that production must run `* * * * * php artisan schedule:run` for the escalation/expiration to fire.
- [x] 14.3 Feature tests: `Scheduler/ExpireBypassTest.php`, `Scheduler/EscalateBypassTest.php` — use `travel()` to advance time.

## 15. Audit event wiring

- [x] 15.1 Cross-check every `$this->audit->record(...)` call site against design §11's catalog. Ensure R1.9, R1.10, R3.17–R3.19, R4.10, R4.13, R5.10, R5.11, R5.12, R8.1 all fire from the right Action/Job.
- [x] 15.2 Extend `AuditService` (or add a decorator) with a `redact(array $metadata): array` helper stripping keys matching `password`, `authorization`, `bearer`, `token`, `secret`. _(R8.3, R11.5)_
- [x] 15.3 Unit test `tests/Unit/Services/AuditServiceRedactionTest.php`.

## 16. Property-based tests

- [x] 16.1 Configure Eris in `phpunit.xml`; create `tests/Property/` suite.
- [x] 16.2 `AttendanceImmutabilityTest` — P1.
- [x] 16.3 `PhOneVerifiedCheckinTest` — P2 (parallel DB transactions, Postgres-only).
- [x] 16.4 `MockLocationNeverProducesAttendanceTest` — P3.
- [x] 16.5 `CrossTenantIsolationTest` — P4.
- [x] 16.6 `AuditLogAppendOnlyTest` — P5.
- [x] 16.7 `MidnightShiftAttributionTest` — P6 (Postgres-only).
- [x] 16.8 `BypassApprovalLinkageTest` — P7.
- [x] 16.9 `ReasonCodeBypassContractTest` — P8.
- [x] 16.10 `AttendanceCompletenessTest` — P9.
- [x] 16.11 `TenantScopedAssignmentVisibilityTest` — P10.
- [x] 16.12 Postgres-only tests skip via the `setUp` pattern in design §15.4.

## 17. Documentation

- [x] 17.1 Update `DOCUMENTATION.md` §7 API Summary with every new `/api/v1/*` endpoint, its request/response shape, and the RFC 7807 error envelope.
- [x] 17.2 Update `DOCUMENTATION.md` §6 Running with the scheduler cron requirement and the new `PH_*` env keys.
- [x] 17.3 Append a "Mobile Officer" section to `README.md` explaining how to access `/officer/login` on a phone and the HTTPS requirement.
- [x] 17.4 Record the Phase 3 scope and the dropped-crypto decisions in a `.kiro/decisions/2026-05-phase-3-crypto-simplification.md` memo so future reviewers can see the rationale.

## 18. Integration + release

- [x] 18.1 `composer test` — full suite (unit + feature + property) green.
- [x] 18.2 `vendor\bin\pint --test` — zero diffs.
- [ ] 18.3 Manual smoke on staging: log in on a phone browser, check in at a seeded location, force-fail geofence, submit bypass, approve it from admin UI, confirm attendance appears on dashboard within 30s.
- [ ] 18.4 Open PR "Phase 3 — Mobile Officer Check-In" with: scope summary, list of dropped features (device binding, JWT bypass, client checksum, HMAC sig), migration order note, testing evidence.
- [ ] 18.5 Feature-flag `PH_OFFICER_API_ENABLED=false` in production `.env` until post-deploy smoke passes.
- [ ] 18.6 After smoke: flip flag to true, tag `v0.3.0`.

---

### Parallel execution hints

- **0–1–2–3** are sequential (infra first). The seeder split (§3) can run in parallel with §4–§5 once §2 migrations are applied.
- **§4, §5, §6** can run in parallel after §2.
- **§7** depends on §4, §5, §6.
- **§8, §9, §10, §11, §12** can run in parallel after §7.
- **§13** depends on §9 (it consumes the API).
- **§14** depends on §7.
- **§15, §16, §17** can run concurrently with §18 integration validation.
