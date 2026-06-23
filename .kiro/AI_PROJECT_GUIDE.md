# AI Agent Project Guide — Police Hazard

**Last Updated:** 2026-06-05  
**Project Status:** Phase 3 Complete (114/118 tasks done)  
**Current Version:** v0.3.0-rc

---

## 🎯 Project Overview

**Police Hazard (PH)** is a GPS-verified attendance tracking system for Indonesian law enforcement (Polri). Officers check in at static checkpoints (Operasi PH) or patrol routes (Operasi Patroli) using mobile web browsers. The system verifies location via PostGIS geofencing, detects GPS spoofing, watermarks photos server-side, and provides real-time dashboards for supervisors.

### Key Facts
- **Tech Stack:** Laravel 13, PHP 8.3, PostgreSQL 16 + PostGIS, Redis, Alpine.js, Tailwind CSS v4
- **Architecture:** Action + Service + Repository pattern (strict layering)
- **Auth:** Laravel session (admin web) + Sanctum tokens (officer mobile API)
- **Tenant Model:** Multi-tenant with hierarchical Saker (POLDA → POLRESTABES → POLSEK)
- **Deployment:** Laragon (local dev), PostgreSQL + MinIO (S3-compatible storage)

---

## 🏗️ Architecture Principles

### Non-Negotiable Rules

1. **Strict Layering**
   - Controllers → FormRequests → Actions → Services/Repositories
   - NO business logic in Controllers or Models
   - NO direct Eloquent in Controllers (always use Repositories)

2. **Tenant Isolation (3 Layers)**
   - PostgreSQL Row-Level Security (RLS)
   - Eloquent `SakerScope` global scope
   - `EnsureSakerContext` middleware
   - **Never weaken any layer**

3. **Immutability**
   - Tables: `attendances`, `audit_logs`, `attendance_amendments`, `manual_bypass_approvals`
   - PostgreSQL rules block UPDATE/DELETE
   - Application code must respect this (insert-only)

4. **Hierarchical Visibility**
   - **God Admin:** sees all Sakers
   - **POLDA Admin:** sees own + all descendants (POLRESTABES, POLSEK)
   - **POLRESTABES Admin:** sees own + POLSEK children
   - **POLSEK Admin:** sees own only
   - **Officers:** strict own-Saker only (security invariant)

5. **UUID v7 Only**
   - All primary keys use UUID v7 (time-ordered)
   - UUID v4 is prohibited

6. **Timezone Rules**
   - Store: UTC (`TIMESTAMPTZ`)
   - Display: WIB/WITA/WIT based on location longitude
   - Shift windows: midnight-spanning allowed (18:00 → 06:00)

7. **Location Coordinate Lock**
   - Once any attendance exists, `coordinates` become immutable
   - Corrections require archive + re-create

---

## 📁 Project Structure

```
app/
  Actions/              # Invokable business operations (one class = one operation)
  Http/
    Controllers/        # Thin orchestrators (validate → Action → response)
      Api/V1/Officer/   # Officer mobile API (Sanctum auth)
    Middleware/         # SecurityHeaders, EnsureSakerContext, RejectMisconfiguredSanctumRoute
    Requests/          # FormRequest validation (StoreXRequest, UpdateXRequest)
  Models/              # Eloquent models (relationships, casts, traits only)
    Concerns/          # HasUuidV7, SakerScope, HasAuditTrail
  Repositories/        # Data access layer (ALL queries go here)
    Contracts/         # Repository interfaces (always depend on these)
  Services/            # Cross-cutting: Audit, Geofence, Notification, Watermark, etc.
  Support/Dtos/        # Data Transfer Objects (CheckinDto, BypassRequestDto, etc.)
  Exceptions/
    Checkin/           # CheckinException hierarchy (typed exceptions)
    Bypass/            # BypassException hierarchy

database/
  migrations/          # Schema + PostGIS + immutability rules
  seeders/             # 10-file split (idempotent, Phase 3)

resources/
  views/
    admin/             # Admin web UI (Blade + Alpine)
    officer/           # Mobile web UI (Blade + Alpine)
  js/
    officer/           # officerApp.js, checkinScreen.js, bypassScreen.js, api.js

routes/
  web.php             # Admin web + officer mobile web routes
  api.php             # Officer mobile API routes (/api/v1/officer/*)
  console.php         # Scheduler (bypass expiration/escalation)

tests/
  Unit/               # Actions, Services, Repositories
  Feature/            # HTTP, API, Migrations
  Property/           # Property-based tests (Eris, 11 test suites)

.kiro/
  specs/              # Feature specs (requirements → design → tasks)
  steering/           # AI steering rules (tech.md, structure.md, product.md)
  decisions/          # Architectural decision records
```

---

## 🔧 Common Tasks

### Development
```cmd
composer dev         # Starts server + queue + logs + Vite
npm run dev         # Vite dev server only
```

### Testing
```cmd
composer test       # Full test suite (122 tests)
php artisan test --filter=CheckinActionTest
php artisan test --testsuite=Unit
```

### Database
```cmd
php artisan migrate:fresh --seed   # Reset + seed (10 seeders)
php artisan db:seed --class=OfficersSeeder
```

### Code Quality
```cmd
vendor\bin\pint              # Auto-fix code style
vendor\bin\pint --test       # Check only
```

---

## 👥 User Roles & Access

| Role | Web Access | API Access | Scope |
|------|-----------|-----------|-------|
| **God Admin** | Full admin UI + cross-tenant heatmap | No | All Sakers |
| **Saker Admin (POLDA)** | Admin UI | No | Own + all descendants |
| **Saker Admin (POLRESTABES)** | Admin UI | No | Own + POLSEK children |
| **Saker Admin (POLSEK)** | Admin UI | No | Own only |
| **Officer** | Mobile web UI only | Mobile API | Own Saker only (strict) |

---

## 🚀 Phase 3: Mobile Officer Check-In (Current)

### Implemented Features (114/118 tasks complete)

✅ **Officer API** (`/api/v1/officer/*`)
- Authentication (Sanctum tokens, no device binding)
- Assignment listing (today ± 7 days, timezone-aware)
- Check-in pipeline (12 steps: GPS → geofence → shift window → spoofing → photo → watermark)
- Manual bypass workflow (supervisor approval queue)
- Attendance history (paginated, photo presigning)

✅ **Mobile Web UI** (`/officer/*`)
- 6 screens: login, assignments (index/show), checkin, bypass, history (index/show)
- GPS acquisition (3-tier fallback: high-accuracy → low-accuracy → watchPosition)
- Camera capture with live preview
- Leaflet map with geofence circle + live distance indicator
- Bundle stashing (GPS + photo persist across check-in → bypass flow)

✅ **Admin Web UI**
- Bypass approval queue (index, show, approve/deny with notes)
- Zone/Assignment detail pages
- Assignment wizard (simplified: no shift picker, inherits operation times)
- Hierarchical visibility (POLDA → POLRESTABES → POLSEK)

✅ **Backend**
- 3 Phase 3 migrations (timezone, bypass extensions, photo narrow transitions)
- 10-file seeder split (idempotent)
- Security headers middleware (X-Request-ID, HSTS, CSP)
- Rate limiting (login, checkin, bypass)
- Dashboard cache invalidation (tag-based)
- Scheduler (bypass expiration/escalation every minute)
- Property-based tests (11 suites: immutability, PH, mock-location, cross-tenant, audit, etc.)

### Remaining Tasks (Manual Release Steps)
- [ ] 18.3 Manual smoke test on staging
- [ ] 18.4 Open PR with scope summary
- [ ] 18.5 Feature-flag `PH_OFFICER_API_ENABLED=false` until smoke passes
- [ ] 18.6 Tag `v0.3.0`

### Dropped Features (Documented in `.kiro/decisions/`)
- Device binding (complexity vs. benefit)
- JWT bypass tokens (plain database workflow instead)
- Client-submitted checksums (server computes internally)
- HMAC supervisor signatures (database-native approval flow)

---

## 🔐 Security Model

### Tenant Isolation
```php
// Layer 1: PostgreSQL RLS (enforced at DB level)
// Layer 2: Eloquent SakerScope (auto-applied to scoped models)
// Layer 3: EnsureSakerContext middleware (validates request context)

// Hierarchical access via User::accessibleSakerIds()
$admin->accessibleSakerIds();  // ['polda-id', 'polrestabes-id', 'polsek-id']
$officer->accessibleSakerIds(); // ['own-saker-id'] (strict)
```

### Immutability
```php
// PostgreSQL rules block UPDATE/DELETE on:
// - attendances
// - audit_logs  
// - attendance_amendments
// - manual_bypass_approvals (except narrow status transitions)

// Application code MUST NOT attempt to mutate these tables
```

### Spoofing Detection
```php
// Multi-signal scoring in ProcessCheckinAction:
// - Mock location flag (instant rejection, never bypassable)
// - Timestamp freshness
// - GPS accuracy
// - Movement speed analysis
// Scores aggregated, threshold check, bypass offered if eligible
```

---

## 📊 Key Domain Models

### Operation Types
- **Operasi PH** (static checkpoint): 1 officer per location per shift
- **Operasi Patroli** (patrol route): multiple officers, no PH-style uniqueness

### Assignment Flow
```
Operation created → Zone created → Locations added to Zone 
→ Shifts auto-created (inherit operation start/end times)
→ Assignment created (Officer + Location + Date + Shift)
→ Officer checks in via mobile
```

### Check-in Flow (12 Steps)
1. Validate assignment exists
2. Check shift window (location timezone)
3. Verify no duplicate attendance
4. Run geofence check (PostGIS `ST_DWithin`)
5. Detect spoofing (multi-signal scoring)
6. Validate photo (MIME, size, magic bytes)
7. Check PH uniqueness (if static checkpoint)
8. Compute server checksum
9. Insert attendance (transaction with advisory lock)
10. Store photo (S3 private bucket)
11. Dispatch watermark job (async)
12. Invalidate dashboard cache

### Bypass Flow
```
Check-in fails (geofence/shift) → Bypass eligible?
→ Officer submits bypass request (with GPS + photo + reason + note)
→ Supervisor sees in approval queue
→ Supervisor approves/denies (with note ≥20 chars)
→ If approved: attendance created from stored bundle, photo watermarked
→ If denied: officer notified, no attendance
→ Auto-escalates after 15/30 min (configurable)
→ Auto-expires after 2 hours
```

---

## 🗄️ Database Schema Highlights

### Tenant-Scoped Tables
- `sakers` (hierarchical: `parent_id` references self)
- `users` (via `saker_id`)
- `operations`, `zones`, `locations`, `shifts`, `assignments`, `attendances`

### Immutable Tables
- `attendances` (narrow exception: `photo_status` pending → processed/failed)
- `audit_logs` (pure append-only)
- `manual_bypass_approvals` (narrow exception: status transitions)

### PostGIS Columns
- `locations.coordinates` (GEOMETRY(POINT, 4326))
- `zones.boundary` (GEOMETRY(POLYGON, 4326))
- Index: GIST on coordinates/boundary

### Unique Constraints
- `assignments`: partial unique on `(officer_id, shift_id, assignment_date)` WHERE `operation_type = 'PH'`
- Advisory lock: `pg_advisory_xact_lock(hashtext(officer_id || assignment_date || shift_id))`

---

## 🧪 Testing Strategy

### Test Suites
- **Unit Tests** (58): Actions, Services, Repositories (mocked dependencies)
- **Feature Tests** (51): HTTP endpoints, API, Migrations, Scheduler
- **Property Tests** (13): Eris-based (immutability, PH uniqueness, cross-tenant, etc.)

### Key Property Tests
1. **AttendanceImmutabilityTest** (P1): Attendances never mutate
2. **PhOneVerifiedCheckinTest** (P2): Only one officer per PH location per shift
3. **MockLocationNeverProducesAttendanceTest** (P3): Mock GPS always rejected
4. **CrossTenantIsolationTest** (P4): Officers never see cross-tenant data
5. **AuditLogAppendOnlyTest** (P5): Audit logs never deleted
6. **MidnightShiftAttributionTest** (P6): Overnight shifts attribute correctly
7. **BypassApprovalLinkageTest** (P7): Approved bypasses always create attendances
8. **ReasonCodeBypassContractTest** (P8): Reason codes match bypass eligibility
9. **AttendanceCompletenessTest** (P9): All attendances have required fields
10. **TenantScopedAssignmentVisibilityTest** (P10): Assignment visibility respects hierarchy

### Running Tests
```cmd
composer test                           # All tests (SQLite in-memory)
php artisan test --testsuite=Property  # Property tests only (Postgres-only tests skip on SQLite)
```

---

## 🐛 Known Limitations & Workarounds

### GPS Distance "10K meters"
- **Issue:** Seeded locations are in Indonesia; dev laptop GPS shows distance as ~10,000+ meters
- **Workaround:** Use browser DevTools → Sensors → Override geolocation to simulate Indonesian coordinates
- **Not a bug:** Geofence logic is correct, just testing from wrong hemisphere

### HTTPS Requirement for GPS
- **Issue:** Browser Geolocation API requires secure context (HTTPS or localhost)
- **Current:** Works on `localhost`; production must use HTTPS
- **Fallback:** App shows "GPS membutuhkan HTTPS" error if not secure context

### Midnight-Spanning Shifts
- **Solution:** Migration `2026_05_30_000001_allow_midnight_spanning_shifts.php` relaxed constraint to allow `shift_end <> shift_start` (was `shift_end > shift_start`)
- **Attribution:** Shift window uses location timezone; check-ins at 01:00 for 18:00–06:00 shift correctly attribute to shift_start date

---

## 📝 Domain Configuration

All domain constants live in `config/policehazard.php`:

```php
'geofence' => [
    'default_radius_meters' => 100,
],
'bypass' => [
    'ttl_minutes' => ['PH' => 15, 'Patroli' => 30],
    'escalation_intervals' => [15, 30], // minutes
],
'spoofing' => [
    'mock_location_weight' => 100,
    'timestamp_max_age_seconds' => 300,
    'accuracy_poor_threshold_meters' => 50,
    'rejection_threshold' => 75,
],
'photo' => [
    's3_disk' => 'minio',
    'max_upload_mb' => 10,
    'watermark_retry' => 3,
],
'auth' => [
    'token_expiry_hours' => 720, // 30 days
    'checkin_rate_limit' => [100, 1], // 100 per minute
],
```

**Never hardcode these values** — always read from config.

---

## 🎨 UI/UX Patterns

### Admin Web
- Dark mode Tailwind CSS
- Blade templates + Alpine.js components
- Sidebar nav with role-based visibility
- Resourceful routing (`/operations`, `/zones`, `/assignments`, etc.)
- Wizards with multi-step forms (operation → zone → location → assignment)

### Mobile Web (Officer)
- Responsive Tailwind CSS (mobile-first)
- Alpine.js state machines (check-in, bypass)
- Leaflet maps (OSM tiles, no API key)
- `sessionStorage` for token + officer profile
- Bundle stashing (GPS + photo persist across navigation)

### API Responses (RFC 7807 Problem Details)
```json
{
  "type": "https://policehazard.id/problems/geofence-violation",
  "title": "Check-in Gagal",
  "status": 400,
  "detail": "Anda berada 150 meter dari titik yang ditugaskan (radius: 100m)",
  "reason_code": "OUTSIDE_GEOFENCE",
  "bypass_eligible": true,
  "extra": {
    "distance_meters": 150,
    "allowed_radius": 100
  },
  "request_id": "019e1234-5678-7abc-def0-123456789abc"
}
```

---

## 🔄 Recent Changes & Fixes

### Context Transfer Summary (Last 8 Fixes)

1. **Officer Login Page Separation** — admin uses `/login`, officers use `/officer/login` (added footer link)
2. **Distance Calculation Fix** — reads `assignment.location_coordinates.lat/lng` (was flat structure)
3. **GPS Acquisition 3-Tier Fallback** — high-accuracy → low-accuracy → watchPosition (handles permission denials)
4. **Camera Preview Race Condition** — polls for video element, explicitly calls `play()`, awaits blob conversion
5. **Bypass Bundle Stashing** — saves GPS + photo to `sessionStorage` when check-in fails with `bypass_eligible=true`
6. **Shift Picker Removal** — wizard now auto-creates shifts matching operation times (no manual selection)
7. **Zone/Assignment Detail Pages** — built complete detail views (were placeholder stubs)
8. **Hierarchical Admin Access** — POLDA sees descendants, officers strict own-Saker; server-side guards on assignment creation

---

## 🚦 When to Ask the User

### Always Ask When:
1. **Relaxing tenant isolation** — any weakening of 3-layer security
2. **Mutating immutable tables** — attempting UPDATE/DELETE on append-only tables
3. **Changing UUID strategy** — switching from v7 to v4
4. **Breaking location lock** — allowing coordinate edits after attendances exist
5. **Adding/removing roles** — changing the God Admin / Saker Admin / Officer model
6. **Modifying spoofing weights** — changing mock-location rejection or threshold values
7. **Altering bypass eligibility** — making mock-location bypassable (currently forbidden)

### Proceed Autonomously:
- Adding new endpoints (follow existing patterns)
- Refactoring within same layer (Action → Action)
- Writing tests (follow existing property/feature test patterns)
- UI improvements (consistency with existing dark mode design)
- Documentation updates
- Bug fixes that don't violate invariants

---

## 📚 Key Files to Read First

### Essential Context
1. `.kiro/steering/product.md` — Product overview & non-negotiable invariants
2. `.kiro/steering/tech.md` — Tech stack & common commands
3. `.kiro/steering/structure.md` — Project structure & layer rules
4. `Police_Hazard_PRD_v2.1.md` — Full product requirements document

### Current Spec
5. `.kiro/specs/mobile-officer-checkin/requirements.md` — Phase 3 requirements
6. `.kiro/specs/mobile-officer-checkin/design.md` — Phase 3 technical design
7. `.kiro/specs/mobile-officer-checkin/tasks.md` — Task list (114/118 done)

### Code Entry Points
8. `routes/api.php` — Officer mobile API routes
9. `routes/web.php` — Admin + officer web routes
10. `app/Actions/ProcessCheckinAction.php` — Check-in pipeline (12 steps)
11. `app/Models/User.php` — Hierarchical access helpers
12. `app/Models/Concerns/SakerScope.php` — Tenant scope implementation
13. `resources/js/officer/checkinScreen.js` — Mobile check-in flow

---

## 💡 Common Patterns

### Creating a New Action
```php
namespace App\Actions;

class DoSomethingAction
{
    public function __construct(
        private SomeRepository $repo,
        private AuditService $audit,
    ) {}

    public function __invoke(SomeDto $dto): Model
    {
        return DB::transaction(function () use ($dto) {
            // 1. Validate
            // 2. Execute core logic via repositories
            // 3. Audit
            $model = $this->repo->create($dto->toArray());
            
            $this->audit->record('SOMETHING_CREATED', $model->id, [
                'detail' => $dto->detail,
            ]);
            
            // 4. Post-commit side effects
            DB::afterCommit(fn() => Cache::tags(['dashboard'])->flush());
            
            return $model;
        });
    }
}
```

### Creating a Repository Method
```php
// Interface in app/Repositories/Contracts/
public function findActive(string $sakerId): Collection;

// Implementation in app/Repositories/
public function findActive(string $sakerId): Collection
{
    return Model::where('is_active', true)
        ->where('saker_id', $sakerId) // or use accessibleSakerIds()
        ->orderBy('created_at', 'desc')
        ->get();
}
```

### Creating an API Endpoint
```php
// 1. FormRequest validation
class StoreThingRequest extends FormRequest {
    public function rules(): array {
        return ['name' => 'required|string|max:255'];
    }
}

// 2. Controller (thin)
class ThingController extends Controller {
    public function store(StoreThingRequest $request, DoSomethingAction $action) {
        $thing = $action(ThingDto::fromRequest($request));
        return response()->json($thing, 201);
    }
}

// 3. Route in routes/api.php
Route::post('/things', [ThingController::class, 'store'])
    ->middleware(['auth:sanctum', 'saker-context']);
```

---

## 🎓 Learning Checklist for New AI Agents

- [ ] Read product.md, tech.md, structure.md (steering rules)
- [ ] Understand the 3-layer tenant isolation model
- [ ] Review immutable tables list (attendances, audit_logs, etc.)
- [ ] Study hierarchical visibility (POLDA → POLRESTABES → POLSEK)
- [ ] Trace a check-in flow through ProcessCheckinAction
- [ ] Understand bypass workflow (request → approval → attendance)
- [ ] Review property-based test contracts (P1–P10)
- [ ] Run `composer test` to confirm baseline
- [ ] Check `.kiro/decisions/` for architectural rationale
- [ ] Review recent bug fixes in context transfer summary

---

## 📞 Support & Resources

- **PRD:** `Police_Hazard_PRD_v2.1.md`
- **Docs:** `DOCUMENTATION.md` (engineering overview)
- **Decisions:** `.kiro/decisions/` (ADRs)
- **Specs:** `.kiro/specs/mobile-officer-checkin/`
- **Steering:** `.kiro/steering/` (AI rules)
- **Tests:** Run `composer test` for full suite
- **Seeding:** `php artisan migrate:fresh --seed` for clean slate

---

**Remember:** This is a law enforcement system with real-world consequences. Tenant isolation, immutability, and GPS verification are security-critical. When in doubt, ask the user before weakening any invariant.
