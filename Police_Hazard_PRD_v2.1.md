# POLICE HAZARD — PRD v2.1
## Command & Control Attendance Management System

**Version:** 2.1.0  
**Date:** May 2026  
**Classification:** `INTERNAL — LAW ENFORCEMENT USE ONLY`  
**Status:** Pre-Development Review  
**Author:** Systems Architecture Office

---

> **CTO MANDATE:** This document is a binding technical contract between the product vision and the engineering team. Any deviation from the constraints defined here — particularly around audit immutability, geospatial validation, tenant isolation, and the architecture patterns in Section 20 — requires formal CTO approval. *"We'll fix it in the next sprint"* is not an acceptable posture for a system that will be subpoenaed.

---

## TABLE OF CONTENTS

1. [Executive Summary](#1-executive-summary)
2. [Stakeholder & Role Matrix](#2-stakeholder--role-matrix)
3. [System Architecture](#3-system-architecture)
4. [Organizational Hierarchy & Multi-Tenancy](#4-organizational-hierarchy--multi-tenancy)
5. [Operational Logic & Business Rules](#5-operational-logic--business-rules)
6. [Database Schema](#6-database-schema-postgresql--postgis)
7. [Functional Requirements by Module](#7-functional-requirements-by-module)
8. [User Stories](#8-user-stories)
9. [API Contract — Mobile Officer Flow](#9-api-contract--mobile-officer-flow)
10. [Mobile Web Officer UI Flow](#10-mobile-web-officer-ui-flow)
11. [Notification System](#11-notification-system)
12. [Export & Reporting Specification](#12-export--reporting-specification)
13. [Security Architecture & Threat Model](#13-security-architecture--threat-model)
14. [Audit & Compliance Framework](#14-audit--compliance-framework)
15. [Testing & QA Requirements](#15-testing--qa-requirements)
16. [Performance & Scalability](#16-performance--scalability)
17. [Non-Functional Requirements](#17-non-functional-requirements)
18. [Open Risks & Architectural Debt Register](#18-open-risks--architectural-debt-register)
19. [Entity Relationship Diagram (ERD)](#19-entity-relationship-diagram-erd)
20. [Architecture Code Patterns](#20-architecture-code-patterns)

---

## 1. EXECUTIVE SUMMARY

### 1.1 Project Overview

**Police Hazard (PH)** is a mission-critical, web-based command-and-control platform for Indonesian law enforcement agencies (Polri). It manages, verifies, and audits officer attendance at Police Hazard static points and mobile patrol routes — replacing unverifiable manual attendance with GPS-verified, photo-documented digital check-ins.

### 1.2 Problem Statement

- Manual attendance at patrol points is unverifiable and subject to falsification
- No real-time operational awareness of which patrol points are covered at any given time
- Absence of a tamper-evident audit trail creates legal and administrative liability
- No mechanism to enforce minimum manning requirements per location
- No data-driven basis for evaluating operational coverage effectiveness

### 1.3 Success Criteria

| Metric | Target | Measurement Method |
|--------|--------|--------------------|
| GPS-verified check-in rate | 100% of events | ST_DWithin validation pass rate |
| Cross-tenant data leaks | Zero | Automated penetration test suite |
| Attendance record immutability | 100% — no UPDATE/DELETE | PostgreSQL rule enforcement |
| Dashboard freshness | Within 30 seconds | Cache invalidation timestamp delta |
| Concurrent check-in capacity | 500 simultaneous | Load test: k6 or Locust |
| Geofence query performance | P95 < 50ms | APM trace on ST_DWithin calls |
| System availability | 99.5% uptime | Uptime monitoring |

### 1.4 Scope Boundaries

**In Scope — v1.0**
- Web-based admin interface (dark mode)
- Mobile-web check-in interface for officers
- GPS geofencing with PostGIS ST_DWithin validation
- Server-side photo watermarking
- Multi-tenant (Saker) data isolation with RLS
- Real-time OpenStreetMap dashboard
- Recapitulation & export reporting
- Manual bypass workflow with supervisor approval
- Comprehensive audit logging
- God Admin heatmap across all Sakers

**Out of Scope — v1.0**
- Payroll or salary system integration
- Officer disciplinary workflow beyond attendance flagging
- Native iOS/Android application (mobile web only)
- Offline-first check-in with sync queue
- Body-worn camera integration

---

## 2. STAKEHOLDER & ROLE MATRIX

### 2.1 User Roles

| Role | Indonesian Term | Scope | Key Capabilities |
|------|----------------|-------|-----------------|
| **God Admin** | Super Administrator | Global / Cross-Saker | Full read/write across all Sakers. Heatmap aggregation. Tenant management. Full audit log access. Can approve any bypass. |
| **Saker Admin** | Administrator Satuan Kerja | Single Saker only | Manage operations, zones, locations, officers within own Saker. Can borrow officers. Approve bypass requests. |
| **Officer** | Anggota | Assigned Locations Only | Mobile check-in only. View own assignments. View own attendance history. Request manual bypass. |

### 2.2 Permission Matrix

| Permission | God Admin | Saker Admin | Officer |
|------------|-----------|-------------|---------|
| View all Saker data | ✅ | ❌ own Saker only | ❌ |
| Create / Edit Operations | ✅ | ✅ own Saker | ❌ |
| Create / Edit Zones | ✅ | ✅ own Saker | ❌ |
| Create / Edit Locations | ✅ | ✅ own Saker | ❌ |
| Assign Officers to Locations | ✅ | ✅ incl. borrowed | ❌ |
| Perform Check-In (Absensi) | ❌ | ❌ | ✅ assigned only |
| View Dashboard | ✅ all Sakers | ✅ own Saker | ❌ |
| View Heatmap | ✅ exclusive | ❌ | ❌ |
| View Recapitulation | ✅ all Sakers | ✅ own Saker | ❌ |
| Approve Manual Bypass | ✅ any Saker | ✅ own Saker | ❌ |
| View Full Audit Logs | ✅ | ✅ read-only | ❌ |
| Manage Saker / Tenants | ✅ exclusive | ❌ | ❌ |
| Export Reports | ✅ | ✅ own Saker | ❌ |
| View God Admin Heatmap | ✅ exclusive | ❌ | ❌ |

### 2.3 Officer Borrowing — Cross-Tenant Rules

When a Saker Admin borrows an officer from another Saker:

- The borrowed officer's **profile data remains owned by their home Saker**
- The **assignment record belongs to the borrowing Saker's operation**
- The home Saker Admin retains **read-only access** to that officer's check-in data for that specific operation
- This cross-tenant read is granted **at the assignment level**, never at the tenant level
- Borrowing does **NOT** transfer any admin rights over the officer's profile

> ⚠️ **WARNING:** A Polsek officer borrowed to a Polda operation cannot be managed by Polda Admin beyond the scope of that specific assignment.

---

## 3. SYSTEM ARCHITECTURE

### 3.1 Technology Stack

| Layer | Technology | Version | Rationale |
|-------|-----------|---------|-----------|
| Backend Framework | PHP / Laravel | 8.3 / 11.x | Service + Action pattern; PostGIS ORM support |
| Architecture Pattern | Service + Action + Repository | — | Business logic never in Controllers or Models |
| Database | PostgreSQL + PostGIS | 16 / 3.4 | ACID, ST_DWithin geofencing, Row-Level Security |
| Cache / Queue | Redis + Laravel Horizon | 7.x | Async photo processing, dashboard cache |
| Frontend (Admin) | Blade + Alpine.js + Tailwind | Latest | Dark-mode interface, Livewire for real-time |
| Frontend (Officer) | Blade + Alpine.js (responsive) | — | Camera API, GPS capture, photo submission |
| Maps | Leaflet.js + OpenStreetMap | 1.9.x | No API cost, marker clustering |
| File Storage | S3-compatible (MinIO / AWS S3) | — | Immutable object storage for check-in photos |
| Authentication (Admin) | Laravel Auth (session) | — | Web session with CSRF protection |
| Authentication (Officer) | Laravel Sanctum (token) | — | API token bound to device_id |
| Image Processing | Intervention Image v3 | 3.x | Server-side watermark rendering |

### 3.2 Architectural Patterns — Non-Negotiable

**Action Classes**
Every discrete business operation is an invokable Action class under `app/Actions/`. Examples: `CreateOperationAction`, `AssignOfficerToLocationAction`, `ProcessCheckinAction`, `ApproveManualBypassAction`. They are the single source of truth for business logic.

**Service Classes**
Domain services orchestrate Action classes and external integrations: `AttendanceService`, `GeofenceService`, `WatermarkService`, `AuditService`, `NotificationService`, `ExportService`.

**Repository Pattern**
All database queries are abstracted behind Repository interfaces. Direct Eloquent calls in Controllers are forbidden. Every Repository method is testable in isolation.

**Global Query Scopes**
All Eloquent models belonging to a Saker implement `SakerScope`. God Admin bypasses this via `SetGodAdminContext` middleware — not by disabling the scope.

### 3.3 UUID Strategy

> ⚠️ **All primary keys use UUID v7 (time-ordered).** UUID v4 is explicitly prohibited. A 500,000-row `attendances` table with UUID v4 PKs will exhibit 50–70% B-tree index fragmentation within 6 months. UUID v7 inserts sequentially like auto-increment but without collision risk. Implement via `ramsey/uuid` with PostgreSQL `uuid` type columns.

### 3.4 Deployment Architecture (Minimum Production)

- 1× Load Balancer (Nginx / AWS ALB) with SSL termination
- 2× Application Servers (Laravel) — active/active for zero-downtime deploys
- 1× PostgreSQL Primary + 1× Read Replica (for reporting queries)
- 1× Redis (Sentinel or Cluster mode for HA)
- 1× Queue Worker (Laravel Horizon) — minimum 2 processes for photo watermarking
- S3-compatible object storage for photos
- Separate monitoring instance (uptime + APM)

---

## 4. ORGANIZATIONAL HIERARCHY & MULTI-TENANCY

### 4.1 Saker (Satuan Kerja) Taxonomy

| Code | Full Name | Level | Typical Parent |
|------|-----------|-------|----------------|
| POLDA | Kepolisian Daerah | Provincial | None (root) |
| POLRESTABES | Kepolisian Resor Kota Besar | City | POLDA |
| POLSEK | Kepolisian Sektor | District | POLRESTABES or POLDA |

### 4.2 Operational Hierarchy

```
Operation  (owned by Saker)
└── Zone   (managed by Saker)
    └── Location  (geospatial point)
        └── Assignment  (Officer + Location + Shift + Date)
            └── Attendance  (check-in record — IMMUTABLE)
```

### 4.3 Tenancy Model: Three-Layer Row-Level Isolation

Tenant isolation is enforced at **three independent layers**. Any single layer failing does not expose data:

**Layer 1 — Database: PostgreSQL Row-Level Security (RLS)**
```sql
CREATE POLICY saker_isolation ON attendances
  USING (saker_id = current_setting('app.current_saker_id')::uuid);
```

**Layer 2 — Application: Eloquent Global Query Scope**
`SakerScope` appended automatically on every Eloquent query for tenant-scoped models.

**Layer 3 — Middleware: Resource-Level Validation**
`EnsureSakerContext` middleware validates `saker_id` match before any controller action executes.

> 📝 **NOTE:** A developer who accidentally disables the Eloquent scope still cannot access cross-tenant data because the middleware and RLS layers remain active. This is defense-in-depth, not redundancy.

---

## 5. OPERATIONAL LOGIC & BUSINESS RULES

### 5.1 Operation Types

| Type | Indonesian Name | Assignment Model | Check-In Model | Success Condition |
|------|----------------|-----------------|----------------|-------------------|
| Police Hazard (PH) | Operasi PH | ONE-TO-ONE: 1 officer per location per shift | Single check-in per assignment. Second attempt rejected. | check_ins >= 1 |
| Patrol (Patroli) | Operasi Patroli | MANY-TO-MANY: Multiple officers per location | Each officer checks in independently | check_ins >= minimum_officer |

**PH Overlap Constraint — Database-Level Enforcement**

```sql
-- Partial unique index prevents overlap at database level
-- Application-layer checks alone insufficient due to race conditions
CREATE UNIQUE INDEX idx_assignments_ph_no_overlap
    ON assignments(officer_id, assignment_date, shift_id)
    WHERE status != 'cancelled';

-- Advisory lock in AssignOfficerAction (REQUIRED — prevents race condition)
SELECT pg_advisory_xact_lock(hashtext(officer_id || assignment_date || shift_id));
```

### 5.2 Attendance Status Definitions

| Status | Definition | Map Color |
|--------|-----------|-----------|
| **Attended** (Hadir) | check_ins >= minimum_officer within shift window | 🟢 Green |
| **Partial** (Sebagian) | check_ins > 0 but < minimum_officer | 🟡 Yellow |
| **Not Attended** (Tidak Hadir) | Zero verified check-ins by shift end | 🔴 Red |
| **Pending** | Shift not yet started | ⚪ Grey |
| **Flagged** | Check-in exists but spoofing signals detected | 🟠 Orange |

### 5.3 Check-In Validation Flow (All Steps Mandatory — Ordered)

1. **Token Authentication** — Validate Sanctum token. Reject if expired or revoked (`TOKEN_INVALID`)
2. **Device Binding Check** — Token must match device_id at issuance. Mismatch → `DEVICE_MISMATCH`, token invalidated
3. **Assignment Lookup** — Officer must have active, non-cancelled assignment for this location today
4. **Shift Window Validation** — Server timestamp (UTC+7) must be within shift_start and shift_end. Reject → `OUTSIDE_SHIFT_WINDOW` + bypass token
5. **Mock Location Detection** — If `mock_location = true`, reject immediately → `MOCK_LOCATION_DETECTED`. **No bypass offered.**
6. **Geofence Validation** — GPS must satisfy `ST_DWithin(officer_location, location_point, radius_meters)`. Reject → `OUTSIDE_GEOFENCE` + bypass token
7. **GPS Accuracy Check** — `gps_accuracy > 50m` triggers `LOW_GPS_ACCURACY` flag. Check-in proceeds but flagged.
8. **Timestamp Drift Check** — Device vs server delta > 60 seconds triggers `TIMESTAMP_DRIFT` flag
9. **Spoofing Multi-Signal Analysis** — Score all signals (Section 13.2). Score >= 2 = auto-reject; score = 1 = flag for review
10. **Duplicate Guard (PH only)** — Reject if assignment already has verified check-in → `CHECKIN_ALREADY_COMPLETED`
11. **Photo Validation** — Validate MIME type via magic bytes. Accept JPEG/PNG only. Max 8MB.
12. **Atomic Write** — Write attendance record inside DB transaction. Dispatch `ProcessCheckinPhoto` job post-commit.
13. **Cache Invalidation** — Invalidate Redis dashboard cache keys for affected operation/zone/location
14. **Response** — Return `attendance_id`, `distance_from_point`, `photo_status`

### 5.4 Manual Bypass Workflow

> Mock location rejections (`MOCK_LOCATION_DETECTED`) are **never eligible** for bypass under any circumstance.

1. Officer receives error response with a `bypass_token` (signed JWT — 15 min for PH, 30 min for Patrol)
2. Officer submits bypass request with `bypass_token` and mandatory `officer_note`
3. System logs `MANUAL_BYPASS_REQUESTED` to `audit_logs` and notifies supervisor
4. Supervisor reviews in web interface: sees officer GPS vs location GPS on comparison map
5. Supervisor approves/denies with mandatory `supervisor_note`. Approval generates HMAC-SHA256 signature.
6. If approved: attendance record created with `is_manual_bypass = true` and linked `bypass_approval_id`
7. If denied or expired: no attendance record created. Officer marked absent.
8. All events are permanently written to `audit_logs`. Neither record is deletable.

### 5.5 Recapitulation Success Rate Formula

```
Success Rate = (Days where check_ins >= minimum_officer) / (Total Active Days) × 100%
```

- **"Active Day"** = any calendar day with at least one active, non-cancelled shift within the selected operation's period
- Days where operation status was `suspended` or `archived` are excluded from both numerator and denominator
- **Midnight-spanning shifts** (e.g., 22:00–06:00): attendance is counted under the date of `shift_start`. This is the governing rule — no exceptions.

### 5.6 Coordinate Mutability Policy — FINAL DECISION

**Location GPS coordinates are LOCKED once any attendance record exists.**

The Edit Location form displays coordinates as read-only with a warning after the first attendance. To correct coordinates: (1) Archive the existing location, (2) Create a new location with correct coordinates, (3) Migrate future assignments manually. Historical attendance records **permanently retain** original coordinates.

> ⚠️ **Rationale:** Changing a location's GPS retroactively changes the geofence used to evaluate past check-ins — a legal and audit integrity violation.

---

## 6. DATABASE SCHEMA (PostgreSQL + PostGIS)

> 📝 **NOTE:** All PKs use UUID v7. All timestamps are `TIMESTAMPTZ`. Soft deletes are NOT used — status fields represent terminal states. Physical `DELETE` is prohibited at the application layer on all core tables.

### 6.1 Tables Summary

| Table | Purpose | Tenant Scoped | Mutable |
|-------|---------|---------------|---------|
| `sakers` | Organizational units | NO (root) | YES |
| `users` | All system users | YES | YES |
| `operations` | Deployment operations | YES | YES (status only after assignment) |
| `zones` | Operational zones | YES | YES |
| `locations` | Geospatial patrol points | YES | PARTIAL (coords locked after first attendance) |
| `shifts` | Time windows for a location | YES (via location) | YES |
| `assignments` | Officer ↔ Location ↔ Shift binding | YES | YES (status transitions only) |
| `attendances` | Check-in records | YES | **NO — append-only** |
| `attendance_amendments` | Correction log | YES | **NO — append-only** |
| `manual_bypass_approvals` | Bypass records | YES | **NO — append-only** |
| `notifications` | In-app notifications | YES | `read_at` only |
| `daily_attendance_summary` | Materialized view for reporting | YES | Auto-refreshed nightly |
| `audit_logs` | Global immutable event log | YES (nullable) | **NO — append-only** |

### 6.2 Core Schema Definitions

#### `sakers`
```sql
CREATE TABLE sakers (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(100) NOT NULL,
    code        VARCHAR(20) NOT NULL UNIQUE,
    type        VARCHAR(20) NOT NULL CHECK (type IN ('POLDA','POLRESTABES','POLSEK')),
    parent_id   UUID REFERENCES sakers(id),
    logo_path   VARCHAR(255),
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

#### `users`
```sql
CREATE TABLE users (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    saker_id    UUID NOT NULL REFERENCES sakers(id),
    name        VARCHAR(100) NOT NULL,
    nrp         VARCHAR(20) NOT NULL UNIQUE,
    email       VARCHAR(150) UNIQUE,
    phone       VARCHAR(20),
    role        VARCHAR(20) NOT NULL CHECK (role IN ('god_admin','saker_admin','officer')),
    safung      VARCHAR(50),
    avatar_path VARCHAR(255),
    password    VARCHAR(255) NOT NULL,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMPTZ,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by  UUID REFERENCES users(id),
    updated_by  UUID REFERENCES users(id)
);
CREATE INDEX idx_users_saker ON users(saker_id);
CREATE INDEX idx_users_nrp ON users(nrp);
CREATE INDEX idx_users_role ON users(role);
```

#### `operations`
```sql
CREATE TABLE operations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    saker_id        UUID NOT NULL REFERENCES sakers(id),
    name            VARCHAR(150) NOT NULL,
    description     TEXT,
    operation_type  VARCHAR(20) NOT NULL CHECK (operation_type IN ('PH','PATROL')),
    status          VARCHAR(20) NOT NULL DEFAULT 'draft'
                        CHECK (status IN ('draft','active','suspended','completed','archived')),
    start_date      DATE NOT NULL,
    end_date        DATE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by      UUID NOT NULL REFERENCES users(id),
    updated_by      UUID REFERENCES users(id),
    CONSTRAINT chk_date_order CHECK (end_date IS NULL OR end_date >= start_date)
);
```

#### `locations` (PostGIS)
```sql
CREATE TABLE locations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    zone_id         UUID NOT NULL REFERENCES zones(id),
    saker_id        UUID NOT NULL REFERENCES sakers(id),
    name            VARCHAR(200) NOT NULL,
    description     TEXT,
    address         TEXT,
    coordinates     GEOMETRY(POINT, 4326) NOT NULL,   -- SRID 4326 (WGS84)
    radius_meters   SMALLINT NOT NULL DEFAULT 50
                        CHECK (radius_meters BETWEEN 10 AND 500),
    minimum_officer SMALLINT NOT NULL DEFAULT 1 CHECK (minimum_officer >= 1),
    padal_id        UUID REFERENCES users(id),
    operating_hours JSONB,      -- {"start":"07:00","end":"19:00"} — DISPLAY ONLY
    coords_locked   BOOLEAN NOT NULL DEFAULT FALSE,   -- TRUE after first attendance
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by      UUID NOT NULL REFERENCES users(id),
    updated_by      UUID REFERENCES users(id)
);

-- Mandatory GIST index for ST_DWithin performance
CREATE INDEX idx_locations_coordinates ON locations USING GIST(coordinates);
CREATE INDEX idx_locations_zone ON locations(zone_id);
CREATE INDEX idx_locations_saker ON locations(saker_id);
```

#### `shifts`
```sql
CREATE TABLE shifts (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    location_id     UUID NOT NULL REFERENCES locations(id),
    name            VARCHAR(100) NOT NULL,
    shift_start     TIME NOT NULL,
    shift_end       TIME NOT NULL,
    active_days     SMALLINT[] NOT NULL,   -- ISO weekdays: [1,2,3,4,5] = Mon–Fri
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_shift_time CHECK (shift_end > shift_start)
);
CREATE INDEX idx_shifts_location ON shifts(location_id);
```

#### `assignments`
```sql
CREATE TABLE assignments (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    officer_id        UUID NOT NULL REFERENCES users(id),
    location_id       UUID NOT NULL REFERENCES locations(id),
    shift_id          UUID NOT NULL REFERENCES shifts(id),
    operation_id      UUID NOT NULL REFERENCES operations(id),
    saker_id          UUID NOT NULL REFERENCES sakers(id),
    assigned_saker_id UUID NOT NULL REFERENCES sakers(id),  -- Borrowing Saker
    assignment_date   DATE NOT NULL,
    status            VARCHAR(20) NOT NULL DEFAULT 'pending'
                          CHECK (status IN ('pending','active','completed','cancelled')),
    notes             TEXT,
    assigned_by       UUID NOT NULL REFERENCES users(id),
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- PH one-to-one enforcement at database level
CREATE UNIQUE INDEX idx_assignments_ph_no_overlap
    ON assignments(officer_id, assignment_date, shift_id)
    WHERE status != 'cancelled';

CREATE INDEX idx_assignments_officer ON assignments(officer_id);
CREATE INDEX idx_assignments_location ON assignments(location_id);
CREATE INDEX idx_assignments_date ON assignments(assignment_date);
CREATE INDEX idx_assignments_operation ON assignments(operation_id);
```

#### `attendances` — IMMUTABLE
```sql
CREATE TABLE attendances (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    assignment_id       UUID NOT NULL REFERENCES assignments(id),
    officer_id          UUID NOT NULL REFERENCES users(id),
    location_id         UUID NOT NULL REFERENCES locations(id),
    saker_id            UUID NOT NULL REFERENCES sakers(id),
    -- GPS
    checkin_coordinates GEOMETRY(POINT, 4326) NOT NULL,
    gps_accuracy_meters NUMERIC(6,2),
    distance_from_point NUMERIC(8,2) NOT NULL,
    is_within_geofence  BOOLEAN NOT NULL,
    -- Timing
    checked_in_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    shift_window_start  TIMESTAMPTZ NOT NULL,   -- Snapshot at check-in time
    shift_window_end    TIMESTAMPTZ NOT NULL,
    is_within_shift     BOOLEAN NOT NULL,
    -- Bypass
    is_manual_bypass    BOOLEAN NOT NULL DEFAULT FALSE,
    bypass_approval_id  UUID REFERENCES manual_bypass_approvals(id),
    -- Status & Flags
    status              VARCHAR(20) NOT NULL DEFAULT 'verified'
                            CHECK (status IN ('verified','flagged','rejected')),
    spoofing_score      SMALLINT NOT NULL DEFAULT 0,
    spoofing_signals    JSONB,
    -- Device
    device_metadata     JSONB NOT NULL,
    photo_path          VARCHAR(500),
    photo_raw_path      VARCHAR(500),
    photo_status        VARCHAR(20) DEFAULT 'pending'
                            CHECK (photo_status IN ('pending','processed','failed')),
    -- Integrity
    checksum            VARCHAR(64) NOT NULL,   -- SHA-256 of key fields
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
    -- No updated_at — INSERT only
);

-- Enforce append-only at database level
CREATE RULE no_update_attendances AS ON UPDATE TO attendances DO INSTEAD NOTHING;
CREATE RULE no_delete_attendances AS ON DELETE TO attendances DO INSTEAD NOTHING;

CREATE INDEX idx_attendances_assignment ON attendances(assignment_id);
CREATE INDEX idx_attendances_officer ON attendances(officer_id);
CREATE INDEX idx_attendances_checkedin ON attendances(checked_in_at);
CREATE INDEX idx_attendances_saker ON attendances(saker_id);
CREATE INDEX idx_attendances_coordinates ON attendances USING GIST(checkin_coordinates);
```

#### `notifications`
```sql
CREATE TABLE notifications (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    recipient_id    UUID NOT NULL REFERENCES users(id),
    saker_id        UUID NOT NULL REFERENCES sakers(id),
    type            VARCHAR(50) NOT NULL,
    title           VARCHAR(200) NOT NULL,
    body            TEXT NOT NULL,
    action_url      VARCHAR(500),
    payload         JSONB,
    read_at         TIMESTAMPTZ,      -- NULL = unread
    expires_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_notifications_recipient ON notifications(recipient_id, read_at);
```

#### `audit_logs` — IMMUTABLE
```sql
CREATE TABLE audit_logs (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    actor_id        UUID REFERENCES users(id),
    actor_ip        INET,
    actor_user_agent TEXT,
    saker_id        UUID REFERENCES sakers(id),
    event_type      VARCHAR(100) NOT NULL,
    entity_type     VARCHAR(50) NOT NULL,
    entity_id       UUID,
    payload_before  JSONB,
    payload_after   JSONB,
    metadata        JSONB,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE RULE no_update_audit_logs AS ON UPDATE TO audit_logs DO INSTEAD NOTHING;
CREATE RULE no_delete_audit_logs AS ON DELETE TO audit_logs DO INSTEAD NOTHING;

-- Application DB role: INSERT-only on audit_logs
GRANT INSERT ON audit_logs TO app_write_role;
REVOKE UPDATE, DELETE ON audit_logs FROM app_write_role;

CREATE INDEX idx_audit_logs_actor ON audit_logs(actor_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_logs_event ON audit_logs(event_type);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);
```

#### `daily_attendance_summary` — Materialized View
```sql
CREATE MATERIALIZED VIEW daily_attendance_summary AS
SELECT
    l.id          AS location_id,
    l.saker_id,
    l.zone_id,
    a.assignment_date AS summary_date,
    COUNT(DISTINCT att.id) AS total_checkins,
    l.minimum_officer,
    CASE
        WHEN COUNT(DISTINCT att.id) >= l.minimum_officer THEN 'attended'
        WHEN COUNT(DISTINCT att.id) > 0                  THEN 'partial'
        ELSE 'not_attended'
    END AS day_status
FROM assignments a
JOIN locations l ON l.id = a.location_id
LEFT JOIN attendances att
    ON att.assignment_id = a.id
    AND att.status = 'verified'
GROUP BY l.id, l.saker_id, l.zone_id, a.assignment_date, l.minimum_officer;

CREATE UNIQUE INDEX ON daily_attendance_summary(location_id, summary_date);

-- Nightly refresh via pg_cron at 23:59 WIB (16:59 UTC)
SELECT cron.schedule(
    'nightly-summary',
    '59 16 * * *',
    'REFRESH MATERIALIZED VIEW CONCURRENTLY daily_attendance_summary'
);
```

---

## 7. FUNCTIONAL REQUIREMENTS BY MODULE

### 7.1 Dashboard (Beranda)

#### Filter Bar

| Filter | Input Type | Behavior |
|--------|-----------|----------|
| Operation Name | Dropdown (single) | Scoped to user Saker. Required before other filters activate. |
| Zone | Dropdown (dependent) | Populated after Operation selected. |
| Date | Date Picker | Defaults to today. Past dates show historical snapshots. |
| Status | Multi-select badges | All Attended / Partial / Not Attended / Pending. Default: all. |
| Officer Name | Text autocomplete | Filters to locations where officer is assigned. |

#### Summary Cards
- **Total Locations** — Count matching filters
- **Fully Attended** — check_ins >= minimum_officer
- **Partially Attended** — 0 < check_ins < minimum_officer
- **Not Attended** — zero verified check-ins

#### Real-Time Map
- Leaflet.js + OpenStreetMap tiles (no API key required)
- Marker clustering via `leaflet.markercluster`
- Marker colors: 🟢 Green (Attended), 🟡 Yellow (Partial), 🔴 Red (Not Attended), ⚪ Grey (Pending), 🟠 Orange (Flagged)
- Map auto-refreshes every 30 seconds via polling
- **On marker click popup shows:** Location Name, Attendance Status, Operation, Zone, Padal name + phone, Address, Operating Hours, Assigned Officers list (name + phone + check-in time)

#### Attendance Detail Table (Below Map)

| Column | Description |
|--------|-------------|
| Avatar | Officer profile photo thumbnail |
| Name + NRP | Full name and employee number |
| Phone | Contact number |
| Status | Attended / Not Attended / Flagged badge |
| Check-In Time | Timestamp if attended |
| Tardiness | Duration from shift start to check-in (red if > 15 min late) |
| Photo | Watermarked photo — expandable lightbox |
| Spoofing Signals | Count if > 0 |
| Report | Supervisor notes or bypass reason |

### 7.2 Recapitulation (Rekapitulasi)

**Filters:** Operation Name (dropdown), Date Range (DD-MM-YYYY to DD-MM-YYYY)

| Column | Description | Sortable |
|--------|-------------|----------|
| Location Name | Patrol point name | ✅ |
| Zone | Parent zone | ✅ |
| Saker | Satuan Kerja code | ✅ |
| Total Active Days | Days with active shifts in range | ✅ |
| Days Attended | check_ins >= minimum_officer | ✅ |
| Days Partial | 0 < check_ins < minimum_officer | ✅ |
| Days Not Attended | Zero check_ins | ✅ |
| Attendance Rate | (Days Attended / Total Active Days) × 100% | ✅ |

- Bottom row: aggregate totals and weighted average rate
- Data sourced from `daily_attendance_summary` materialized view
- Export buttons: PDF (landscape A4) and Excel (.xlsx)

### 7.3 Operation Page (Operasi)

| Column | Description |
|--------|-------------|
| Name | Operation name |
| Description | Brief description |
| Operation Type | PH / Patrol (colored badge) |
| Start / End Date | Date range |
| Status | Draft / Active / Suspended / Completed / Archived |
| Actions | Detail · Edit · Archive |

> 📝 **Operation Type is IMMUTABLE after the first Zone is created.** Archive is **blocked** if any assignment has status `pending` or `active`. System returns HTTP 422 with list of blocking assignments. Message: *"Operasi tidak dapat diarsipkan karena masih terdapat X penugasan aktif. Batalkan semua penugasan terlebih dahulu."*

### 7.4 Zone Page (Zona)

| Column | Description |
|--------|-------------|
| Zone Name | Name of zone |
| Operation | Parent operation + type badge |
| Saker | POLDA / POLRESTABES / POLSEK badge |
| Location Count | Number of active locations |
| Description | Zone description |
| Actions | Detail · Edit · Delete |

> Delete is blocked if the zone has active locations.

### 7.5 Location Page (Lokasi)

**Add/Edit Form Fields:**

| Field | Type | Validation |
|-------|------|-----------|
| Name | Text | Required. Max 200 chars. |
| Operation | Dropdown | Required |
| Zone | Dropdown | Required, filtered by operation |
| Padal | Officer search dropdown | Required |
| Description | Textarea | Optional |
| Coordinates | Map pin-drop + manual lat/lng | Required. Locked after first attendance. |
| Radius | Number (meters) | Required. Range: 10–500m. Default: 50m. |
| Minimum Officer | Number | Required. Min: 1. |
| Operating Hours | Time range | **Display only** — not an enforcement constraint |

### 7.6 Officer Page (Anggota)

**Default sort: Tardiness (longest delay to check-in at top)**

| Column | Description |
|--------|-------------|
| Avatar | Profile photo |
| Name + NRP | Full name and employee number |
| Saker + Safung | Unit and functional staff role |
| Location | Currently assigned location (today) |
| Total Check-Ins | For filtered date range |
| Min. Officer | Required for assigned location |
| Last Check-In Time | Timestamp |
| Tardiness | Duration from shift_start to check-in |
| Status | Attended / Not Attended / Flagged |

### 7.7 Assignment Page (Penugasan)

**Create Assignment Flow:**
1. Select Operation (determines PH or Patrol type)
2. Select Zone
3. Select Location(s)
4. Select Date or Date Range
5. Select Shift(s)
6. Search and select Officer(s) via NRP or name autocomplete
7. System validates: PH overlap guard, officer active status, operation date range
8. Bulk confirmation screen — preview all records before submission

### 7.8 God Admin Heatmap (Peta Panas)

**Exclusive to God Admin role.**

| Layer | Visualization | Data Source |
|-------|--------------|-------------|
| Attendance Coverage | Choropleth by zone — darker = lower rate | `daily_attendance_summary` |
| Absence Clustering | Leaflet.heat density — unattended locations | `attendances WHERE day_status = 'not_attended'` |
| Spoofing Incidents | Red point markers per flagged check-in | `attendances WHERE spoofing_score >= 1` |
| Officer Density | Dot density — assignments per zone | `assignments` by zone |

**Filters:** Date Range (up to 90 days), Saker Level, Operation Type, Layer Toggle

> ⚠️ **Heatmap must not be accessible via URL to non-God-Admin roles. Route-level authorization middleware must enforce this — not just UI hiding.**

---

## 8. USER STORIES

### 8.1 God Admin User Stories

| Story ID | User Story | Acceptance Criteria |
|----------|-----------|---------------------|
| GA-01 | As a God Admin, I want to view attendance heatmaps across all Sakers so that I can identify chronically unattended zones. | Loads within 3s for 90-day range. All Saker data visible. Layer toggles independent. Not accessible to other roles. |
| GA-02 | As a God Admin, I want to create and manage Saker records so that new police units can be onboarded. | Can create Saker with code, type, parent. Logo upload functional. New Saker Admin only sees own data immediately. |
| GA-03 | As a God Admin, I want to approve bypass requests from any Saker so that I act as escalation when Saker Admin is unreachable. | Pending requests from all Sakers appear in queue. Filterable. Approval generates HMAC signature + audit log. Officer notified. |
| GA-04 | As a God Admin, I want to view the complete audit log across all Sakers so that I can investigate compliance incidents. | Filterable by Saker, event type, actor, date. No event is modifiable. Export to PDF with filter parameters. |
| GA-05 | As a God Admin, I want to see a cross-Saker attendance rate comparison so that I can identify underperforming units. | Bar chart per Saker for selected date range. Click drills down. Data matches individual Saker Admin views. |

### 8.2 Saker Admin User Stories

| Story ID | User Story | Acceptance Criteria |
|----------|-----------|---------------------|
| SA-01 | As a Saker Admin, I want to create a PH operation so that officers can be assigned to static patrol points. | Operation type immutable after first zone. Appears in dashboard filter immediately. Scoped to own Saker only. |
| SA-02 | As a Saker Admin, I want to assign officers to locations for specific dates and shifts. | PH: blocks if officer already assigned same date/shift. Patrol: multiple officers simultaneously. Bulk creates individual records. Officer notified. |
| SA-03 | As a Saker Admin, I want to borrow officers from other Sakers for an operation. | Officer search shows all Sakers. Borrowed officer shown with home Saker badge. Home Saker Admin sees read-only. No profile admin rights transferred. |
| SA-04 | As a Saker Admin, I want to view the real-time dashboard for my Saker. | Loads within 2s. Markers update within 30s. Marker click shows officer details + photos. Own Saker data only. |
| SA-05 | As a Saker Admin, I want to generate a recapitulation report for a date range. | Formula matches documented calculation. PDF export professional with Saker logo. Excel has correct data types. |
| SA-06 | As a Saker Admin, I want to approve or deny manual bypass requests. | Requests appear as badge in nav. Comparison map shown. Mandatory reason required. Denial prevents attendance record. |
| SA-07 | As a Saker Admin, I want to view spoofing-flagged check-ins. | Orange badge on flagged entries. Detail shows which signals triggered. Can mark Reviewed Legitimate or Confirmed Spoofing. Both actions logged. |
| SA-08 | As a Saker Admin, I want to set geofence radius per location. | Range 10–500m. Circle overlay on map. Radius change after first attendance requires confirmation. Historical records retain radius active at check-in time. |

### 8.3 Officer User Stories

| Story ID | User Story | Acceptance Criteria |
|----------|-----------|---------------------|
| OF-01 | As an Officer, I want to log in with my NRP and password on my mobile browser. | Works on Chrome Mobile 90+ and Safari Mobile 15+. Clear errors for wrong credentials vs. disabled. 5 failures = 15-min lockout. Token persists 24 hours. |
| OF-02 | As an Officer, I want to see my assignments for today. | Shows location, address, shift time, map preview. Future dates visible but check-in blocked. Attended = green checkmark. Past unattended = red. |
| OF-03 | As an Officer, I want to check in by taking a photo and submitting GPS. | Check-in button only active during shift + within geofence. Camera via browser MediaDevices API. Photo preview before submit. Success shows time + distance + watermarked thumbnail. Completes within 10s on 4G. |
| OF-04 | As an Officer, I want to see my distance from the assigned location before check-in. | Live distance counter updates every 5 seconds. Green if within radius, red if outside. Accuracy warning if GPS > 30m. |
| OF-05 | As an Officer, I want to request a manual bypass when I cannot check in normally. | Available ONLY for OUTSIDE_GEOFENCE or OUTSIDE_SHIFT_WINDOW. NOT available for MOCK_LOCATION_DETECTED. Mandatory note (min 20 chars). Auto-checks status every 30 seconds. |
| OF-06 | As an Officer, I want to view my attendance history. | Last 30 days default. Each entry: date, location, shift, status, time, distance. Watermarked photo viewable fullscreen. |
| OF-07 | As an Officer, I want to be notified when my bypass request is resolved. | In-app notification on next page load. Shows supervisor name, decision, reason. Denied: shows alternative options. |

---

## 9. API CONTRACT — MOBILE OFFICER FLOW

> 📝 All endpoints require HTTPS. All officer requests require `Authorization: Bearer {token}`. All responses include `X-Request-ID` header. Error responses follow RFC 7807 Problem Details format.

### 9.1 Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/v1/auth/login` | Officer login — returns token bound to device_id | NO |
| POST | `/api/v1/auth/logout` | Revoke current token | YES |
| POST | `/api/v1/auth/refresh` | Issue new token (valid old token required) | YES |

**POST `/api/v1/auth/login` — Request**
```json
{
  "nrp": "string",
  "password": "string",
  "device_id": "string",
  "device_info": {
    "os": "android|ios|web",
    "os_version": "string",
    "app_version": "string",
    "model": "string"
  }
}
```

**Response 200**
```json
{
  "token": "string",
  "token_expires_at": "ISO8601",
  "officer": {
    "id": "uuid",
    "name": "string",
    "nrp": "string",
    "saker": "string",
    "avatar_url": "string"
  }
}
```

### 9.2 Assignment Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/officer/assignments` | List assignments. Query: `?date=YYYY-MM-DD` (default: today) |
| GET | `/api/v1/officer/assignments/{id}` | Single assignment detail with location map data |
| GET | `/api/v1/officer/assignments/{id}/distance` | Live distance from current GPS to assignment location |

### 9.3 Check-In Endpoint

**POST `/api/v1/officer/checkin`** — `Content-Type: multipart/form-data`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `assignment_id` | uuid | YES | |
| `latitude` | decimal | YES | -90 to 90 |
| `longitude` | decimal | YES | -180 to 180 |
| `gps_accuracy` | decimal | YES | Device-reported accuracy in meters |
| `gps_altitude` | decimal | NO | For spoofing detection |
| `gps_speed` | decimal | NO | For speed plausibility check |
| `gps_provider` | string | YES | `"gps"` \| `"network"` \| `"fused"` |
| `timestamp_device` | ISO8601 | YES | Device clock — for drift detection |
| `mock_location` | boolean | YES | Device-reported flag |
| `photo` | file | YES | JPEG or PNG. Max 8MB. Magic byte validated. |
| `checksum` | string | YES | SHA-256 of: `assignment_id+lat+lng+timestamp_device` |

**Response Codes**

| HTTP | Error Code | Bypass Issued | Description |
|------|-----------|---------------|-------------|
| 200 | — | NO | Check-in verified |
| 403 | `MOCK_LOCATION_DETECTED` | **NEVER** | Hard reject. No bypass possible. |
| 403 | `OUTSIDE_SHIFT_WINDOW` | YES | Bypass eligible |
| 422 | `OUTSIDE_GEOFENCE` | YES | Bypass eligible |
| 409 | `CHECKIN_ALREADY_COMPLETED` | NO | PH only |
| 422 | `ASSIGNMENT_NOT_FOUND` | NO | |
| 422 | `INVALID_CHECKSUM` | NO | Potential tampering |
| 422 | `PHOTO_INVALID` | NO | Failed MIME/magic byte check |
| 429 | `RATE_LIMITED` | NO | 10 attempts/officer/minute |

**Success Response 200**
```json
{
  "status": "success",
  "attendance_id": "uuid",
  "checked_in_at": "2026-05-07T07:42:31+07:00",
  "distance_from_point": 23.4,
  "is_flagged": false,
  "photo_status": "pending"
}
```

### 9.4 Other Officer Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/officer/bypass-request` | Submit bypass using `bypass_token` from failed check-in |
| GET | `/api/v1/officer/bypass-request/{id}` | Poll bypass status |
| GET | `/api/v1/officer/attendance/history` | Own attendance history. Query: `?from=&to=&page=` |
| GET | `/api/v1/officer/attendance/{id}` | Single attendance detail with photo URL |
| GET | `/api/v1/officer/notifications` | Unread notifications |
| PATCH | `/api/v1/officer/notifications/{id}/read` | Mark notification as read |

### 9.5 Admin Dashboard Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/dashboard/stats` | Summary cards data |
| GET | `/api/v1/admin/dashboard/map-points` | GeoJSON FeatureCollection of location markers |
| GET | `/api/v1/admin/dashboard/location/{id}/detail` | Location popup data + officer check-ins |
| GET | `/api/v1/admin/heatmap/data` | God Admin only. Spatial data for heatmap layers. |

### 9.6 Photo Watermark Specification

| Element | Position | Content |
|---------|----------|---------|
| Saker Logo | Top-right | PNG from `sakers.logo_path`. Max 80×80px. 80% opacity. |
| Officer Name + NRP | Bottom banner, line 1 | Bold white on semi-transparent dark overlay |
| Location Name | Bottom banner, line 2 | Full location name |
| GPS Coordinates | Bottom banner, line 3 | `-6.208843, 106.845600` (6 decimal places) |
| Date + Time | Bottom banner, line 4 | `DD-MM-YYYY HH:MM:SS WIB` |
| Distance | Bottom banner, line 5 | `Jarak: 23.4m dari titik` |

> 📝 If `ProcessCheckinPhoto` job fails after 3 retries: `photo_status = 'failed'`, supervisor notified, raw photo retained. Check-in record remains legally valid.

---

## 10. MOBILE WEB OFFICER UI FLOW

### Screen 1: Login
- Fields: NRP (numeric keyboard), Password (masked)
- Errors: wrong credentials / account disabled / account locked (X minutes remaining)
- On success: redirect to Today's Assignments
- Token stored in `sessionStorage` (**NOT** `localStorage` — clears when tab closes)

> ⚠️ `sessionStorage` is intentional — prevents shared-device credential persistence.

### Screen 2: Today's Assignments (Home)
- Header: officer name, NRP, Saker, avatar
- Date switcher: ± 7 days from today
- Assignment cards sorted by shift start time
- Card: Location name, shift window, status badge (Pending / Attended / Not Attended / Flagged)
- Bell icon: notification count badge

### Screen 3: Assignment Detail
- Location name + full address
- Mini-map with location pin + geofence radius circle
- **Live distance indicator** — updates every 5 seconds via Geolocation API
  - 🟢 Green if within radius
  - 🔴 Red if outside
  - 🟡 Amber if GPS accuracy > 30m
- Shift window countdown timer if not yet started
- **CHECK-IN button:** disabled (grey) if outside shift or already attended; enabled (green) if conditions met

### Screen 4: Check-In Flow (Step-by-Step)
1. User taps CHECK-IN button
2. GPS acquired — spinner: *"Mengambil lokasi GPS..."* (timeout: 30 seconds)
3. Geofence pre-validation — if outside, show distance + offer bypass or cancel
4. Camera opens via `MediaDevices.getUserMedia({ video: { facingMode: "user" } })`
5. Photo preview shown before submitting
6. Submit: *"Kirim Absensi"*
7. Loading: *"Memproses absensi..."*
8. **Success:** green checkmark, check-in time, distance, *"Absensi berhasil tercatat"*
9. **Failure:** specific error code + next steps (bypass link if eligible)

### Screen 5: Manual Bypass Request
- Triggered by `OUTSIDE_GEOFENCE` or `OUTSIDE_SHIFT_WINDOW` only
- Banner showing rejection reason and distance/time delta
- Mandatory text area (min 20 characters): *"Keterangan"*
- Pending screen auto-polls status every 30 seconds
- Approved → success animation + check-in recorded
- Denied/Expired → clear message with supervisor note

### Browser Compatibility

| Browser | Minimum Version |
|---------|----------------|
| Chrome Mobile (Android) | 90+ |
| Safari Mobile (iOS) | 15+ |
| Samsung Internet | 14+ |
| Firefox Mobile | 90+ |

> ⚠️ Camera access requires HTTPS. The application **must** be served over HTTPS in all environments including local development (`mkcert` for local HTTPS). HTTP = no Camera API = broken check-in flow.

---

## 11. NOTIFICATION SYSTEM

### 11.1 Channels

| Channel | Use Cases | Implementation | Fallback |
|---------|-----------|----------------|----------|
| In-App (Web) | All notifications — primary channel | Database polling (60-second interval) on page load | — |
| Email | Bypass request to supervisor, account activation, password reset | Laravel Mailables via SMTP, queued | Log to file if SMTP fails |
| SMS (v1.1) | Bypass if supervisor unresponsive for 5 minutes | Third-party SMS gateway | Escalate to God Admin |

### 11.2 Notification Catalogue

| Type | Trigger | Recipient | Expiry | Action |
|------|---------|-----------|--------|--------|
| `BYPASS_REQUEST` | Officer submits bypass | Saker Admins + God Admin | 15/30 min | Review Request |
| `BYPASS_APPROVED` | Supervisor approves | Requesting Officer | None | View Check-In |
| `BYPASS_DENIED` | Supervisor denies | Requesting Officer | None | View Reason |
| `BYPASS_EXPIRED` | Token expires without response | Officer + Supervisor | None | — |
| `SPOOFING_ALERT` | spoofing_score >= 1 | Saker Admin | None | Review Check-In |
| `ASSIGNMENT_CREATED` | New assignment for officer | Assigned Officer | None | View Assignment |
| `ASSIGNMENT_CANCELLED` | Assignment cancelled | Assigned Officer | None | — |
| `PHOTO_FAILED` | Watermark job fails after 3 retries | Saker Admin | None | View Record |
| `SHIFT_STARTING` | 30 min before shift_start (if unattended) | Assigned Officer | At shift end | View Assignment |

### 11.3 Supervisor Escalation for Bypass Requests

| Time | Action |
|------|--------|
| T+0 | Bypass submitted. Notification to all Saker Admins. |
| T+5 min | No response → escalate to God Admin |
| T+10 min | No response → email to Saker Admin |
| T+15 min (PH) / T+30 min (Patrol) | Token expires. Officer marked absent. Final notification to all admins. |

> 📝 Escalation timers checked by Laravel scheduled command running every minute. Never use `sleep()` or blocking delays in application layer.

---

## 12. EXPORT & REPORTING SPECIFICATION

### 12.1 Available Exports

| Export | Format | Source | Contents |
|--------|--------|--------|----------|
| Recapitulation Report | PDF (landscape) + Excel (.xlsx) | Rekapitulasi page | All table columns + aggregate + filter params in header |
| Dashboard Attendance | PDF (portrait) | Dashboard page | Current filter state, summary cards, officer table |
| Audit Log Export | CSV + PDF | Audit Log page | All filtered audit events |
| Officer Attendance History | PDF | Officer Detail page | Profile + full attendance history for date range |

### 12.2 PDF Specification (Recapitulation — Landscape A4)

**Page 1 Header:** Police Hazard logo (left), Saker name (center), `LAPORAN REKAPITULASI KEHADIRAN` (right)

**Metadata block:** Operation, Date range, Generated by (admin name + timestamp WIB), Saker

**Main table:** All columns from Section 7.2. Alternating row shading. Header repeated on every page.

**Last row:** Bold, dark blue background, white text — aggregate totals.

**Footer:** `Page N of N` | timestamp | `INTERNAL — LAW ENFORCEMENT USE ONLY`

### 12.3 Export Security
- All exports generated **server-side** — no client-side CSV generation
- Export action logged in `audit_logs` with filter parameters and row count
- God Admin cross-Saker exports include watermark: `LINTAS SATUAN KERJA — RAHASIA`
- Export file URLs are **presigned S3 URLs** (15-minute expiry) — not persistent file paths
- Saker Admin URL-manipulation attempts to export another Saker's data are blocked by the same `SakerScope`

---

## 13. SECURITY ARCHITECTURE & THREAT MODEL

### 13.1 Threat Matrix

| Threat | Actor | Impact | Mitigation |
|--------|-------|--------|-----------|
| GPS Spoofing (Mock Location) | Officer | Fake patrol presence | Multi-signal detection (Section 13.2). Mock flag = instant hard reject. |
| Photo Substitution | Officer | Pre-taken photo submitted | Server validates photo within 60s of check-in. EXIF stripped, timestamp watermarked. |
| Cross-Tenant Data Access | Saker Admin | Data breach | RLS + Eloquent Scope + Middleware (defense-in-depth) |
| Privilege Escalation | Saker Admin | Gain God Admin access | Role changes require God Admin + 2FA. Logged in audit. |
| Attendance Falsification | Saker Admin | Record manipulation | Append-only tables. Amendment workflow. |
| API Token Theft | External | Impersonate officer | Token bound to device_id. IP change = re-auth required. |
| Brute Force Login | External | Account compromise | 5 attempts / 15 min per IP + per NRP. Exponential backoff. |
| SQL Injection | External | Data exfiltration | Eloquent parameterized queries only. Raw queries prohibited. |
| IDOR | Any user | Unauthorized resource access | Laravel Policies on every resource. UUID non-guessability. |
| Inactive Officer Access | Disabled officer | Continue checking in | Token revocation on deactivation via `TokenRevocationJob`. |
| Dashboard Polling DoS | Any | Server overload | Rate-limited to 1 req/30s per user. Cache-first. |

### 13.2 GPS Spoofing Detection — Multi-Signal Scoring

Each signal = +1 to `spoofing_score`. Score = 1 → flagged for review. Score >= 2 → auto-rejected.

| Signal | Score | Detection | Threshold |
|--------|-------|-----------|-----------|
| Mock Location Flag | +1 (auto-reject alone) | Device `isFromMockProvider = true` | Any TRUE = instant reject |
| Suspicious GPS Accuracy | +1 | Accuracy value | accuracy < 3.0 meters |
| Speed Plausibility | +1 | Distance from last position / elapsed time | Implied speed > 200 km/h |
| Timestamp Drift | +1 | Server time vs device timestamp | Delta > 60 seconds |
| Network-Only Provider | +1 | `gps_provider` field | `"network"` with accuracy < 5m |
| Repeated Exact Coordinates | +1 | Compare to last 3 check-ins | Identical to 4+ decimal places |

> 📝 `spoofing_score` and `spoofing_signals` JSONB are stored on **every** check-in regardless of score — creating a behavioral baseline.

### 13.3 File Upload Security
- MIME validation via **magic bytes** (first 12 bytes), NOT file extension
- Accepted: `image/jpeg`, `image/png` only
- Max size: 8MB enforced at both Nginx and application level
- All files stored **outside webroot**
- Served via presigned S3 URLs only
- EXIF metadata stripped before storage
- Filename replaced with `attendance UUID` — original filename never stored

### 13.4 HTTP Security Headers

| Header | Value |
|--------|-------|
| `Content-Security-Policy` | `default-src 'self'; img-src 'self' data: *.tile.openstreetmap.org; script-src 'self' 'nonce-{random}'` |
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `geolocation=(self), camera=(self)` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` |

---

## 14. AUDIT & COMPLIANCE FRAMEWORK

### 14.1 Audit Events Catalogue

| Event Type | Trigger | Key Fields |
|-----------|---------|------------|
| `USER_LOGIN` | Successful login | actor_id, IP, device_id, timestamp |
| `USER_LOGIN_FAILED` | Failed attempt | attempted NRP, IP, failure reason |
| `USER_DEACTIVATED` | Account disabled | actor, target user, reason |
| `CHECKIN_SUBMITTED` | Check-in submitted | all GPS fields, assignment, spoofing_score |
| `CHECKIN_FLAGGED` | Spoofing signals triggered | which signals, values |
| `CHECKIN_REJECTED` | Check-in fails validation | reason, GPS coordinates, distance |
| `MANUAL_BYPASS_REQUESTED` | Bypass submitted | officer, location, reason, submitted GPS |
| `MANUAL_BYPASS_APPROVED` | Supervisor approves | supervisor, signature_hmac, note |
| `MANUAL_BYPASS_DENIED` | Supervisor denies | supervisor, note |
| `ASSIGNMENT_CREATED` | New assignment | all assignment fields, creator |
| `ASSIGNMENT_CANCELLED` | Assignment cancelled | actor, reason, affected officer |
| `OPERATION_STATUS_CHANGED` | Status transition | old status, new status, actor |
| `LOCATION_COORDINATES_CHANGED` | GPS moved (pre-lock) | old + new coordinates, actor |
| `LOCATION_COORDS_LOCK_ATTEMPTED` | Change attempted on locked coords | actor, attempted coordinates, blocked |
| `OFFICER_ROLE_CHANGED` | Role update | old role, new role, actor, 2FA confirmed |
| `DATA_EXPORTED` | Report exported | actor, filter params, row count, format |
| `TOKEN_REVOKED` | Token invalidated | reason (logout / deactivation / role change) |
| `ATTENDANCE_AMENDMENT_APPROVED` | Amendment approved | approver, original + amended values |

### 14.2 Audit Log Integrity
- Append-only PostgreSQL rules (`CREATE RULE`) block UPDATE/DELETE
- Application DB role has INSERT-only privileges on `audit_logs`
- Every audit entry includes `request_id` UUID for distributed tracing
- Weekly: hash chain checkpoint computed and stored in `audit_checkpoints` for tamper detection
- `AuditService` called from **Action classes**, not Controllers

### 14.3 Data Retention

| Data | Active | Warm Archive | Cold Archive | Deletion |
|------|--------|-------------|-------------|----------|
| Attendance records | Indefinite | — | — | **NEVER** |
| Audit logs | Indefinite | — | — | **NEVER** |
| Photos (watermarked) | 90 days S3 Standard | 2 years S3 IA | 5 years Glacier | After 5 years (manual review) |
| Photos (raw) | 7 days max | Deleted if watermark succeeded | — | 7 days max |
| Notifications | 90 days active | Archived to `notification_archive` | — | 2 years total |

> ⚠️ Physical DELETE is only permitted on session and notification data. **Never** on attendance, audit, assignment, or amendment records.

---

## 15. TESTING & QA REQUIREMENTS

### 15.1 Coverage Targets

| Layer | Minimum Coverage | Framework |
|-------|----------------|-----------|
| Action Classes | 90% line coverage | PHPUnit |
| Service Classes | 85% line coverage | PHPUnit |
| Repository Classes | 80% line coverage | PHPUnit + SQLite in-memory |
| API Endpoints | 100% endpoint coverage | Laravel HTTP Feature tests |
| Geofence Logic | 100% — all edge cases | PHPUnit with live PostGIS DB |
| PH Overlap Guard | 100% incl. race condition | PHPUnit with parallel transactions |
| Audit Log writes | 100% — every Action class | PHPUnit mock AuditService |
| Spoofing Detection | 100% — every signal path | PHPUnit unit tests |
| Frontend critical flows | Login, Check-In, Bypass | Playwright or Cypress E2E |

### 15.2 Mandatory Release-Blocker Test Cases

| Test ID | Description | Expected Result |
|---------|-------------|----------------|
| **T-01** | Check-in exactly at geofence boundary (= radius_meters) | ACCEPTED — ST_DWithin is inclusive |
| **T-02** | Check-in at radius + 1 meter outside | REJECTED: `OUTSIDE_GEOFENCE` + bypass token |
| **T-03** | Two concurrent PH assignment requests, same officer/date/shift | First succeeds. Second: DB constraint violation, handled gracefully. |
| **T-04** | Check-in with `mock_location = true` | REJECTED: `MOCK_LOCATION_DETECTED`. No bypass token. Audit log entry created. |
| **T-05** | Direct SQL `UPDATE` on `attendances` table | Row unchanged. PostgreSQL rule blocks it. Returns 0 rows affected. |
| **T-06** | Direct SQL `DELETE` on `audit_logs` table | Row unchanged. PostgreSQL rule blocks it. |
| **T-07** | Saker Admin API request with another Saker's resource UUID | HTTP 403. Audit: `UNAUTHORIZED_ACCESS_ATTEMPT`. |
| **T-08** | God Admin bypass of Saker scope | Can read all Saker data. Writes still scoped to explicit Saker parameter. |
| **T-09** | Valid check-in during shift window at correct location | HTTP 200 with `attendance_id`. Record created. Assignment status → active. |
| **T-10** | Bypass request with expired `bypass_token` | HTTP 422: `BYPASS_TOKEN_EXPIRED`. No bypass record created. |
| **T-11** | Officer deactivated while holding valid API token | `TokenRevocationJob` fires. Next API request returns 401. |
| **T-12** | Recapitulation for midnight-spanning shift (22:00–06:00) | Attendance counted under `shift_start` date. Not split across two days. |
| **T-13** | Location coordinate change after first attendance | HTTP 422. `coords_locked = true`. Blocked attempt logged in audit. |
| **T-14** | PHP file upload with `.jpg` extension | HTTP 422: `PHOTO_INVALID`. Magic byte validation rejects non-JPEG/PNG. |
| **T-15** | Dashboard map-points with 500 locations | P95 < 500ms. Verified by load test. |

### 15.3 Load Testing Scenarios

| Scenario | Target Load | Success Criteria |
|----------|-------------|----------------|
| Concurrent check-ins | 500 simultaneous POST /checkin | P99 < 3s. Zero data corruption. All 500 records created correctly. |
| Dashboard polling | 200 admins polling every 30s | P95 < 500ms. Redis cache hit rate > 95%. |
| Geofence validation | 1,000 ST_DWithin queries/second | P99 < 50ms. GIST index confirmed in EXPLAIN ANALYZE. |
| Recapitulation | 50 concurrent reports (90 days, 300 locations) | P99 < 10s. Data from materialized view. |

---

## 16. PERFORMANCE & SCALABILITY

### 16.1 Query Performance Targets

| Query | Target P95 | Key Optimization |
|-------|-----------|-----------------|
| Dashboard map load (500 locations) | < 500ms | GIST index + Redis cache 30s TTL |
| Geofence validation (ST_DWithin) | < 50ms | GIST index on `locations.coordinates`, `::geography` cast |
| Check-in end-to-end | < 2 seconds | Photo processing async (queued). DB write only. |
| Recapitulation (90 days, 200 locations) | < 3 seconds | `daily_attendance_summary` materialized view |
| Officer list with filters (1,000 officers) | < 500ms | Composite indexes on filter columns |

### 16.2 Caching Strategy

| Data | Cache Key | TTL | Invalidation |
|------|-----------|-----|-------------|
| Dashboard stats | `dashboard:stats:{saker}:{op}:{date}` | 30s | Any new attendance for that operation |
| Map points GeoJSON | `map:points:{op}:{zone}:{date}` | 30s | Any attendance or assignment change |
| Recapitulation | `recap:{op}:{from}:{to}:{saker}` | 10 min | Any attendance in date range |
| Location list | `locations:{op}:{zone}` | 5 min | Location create/edit/delete |
| Saker logo | `logo:{saker_id}` | 24h | Logo update |

### 16.3 Mandatory Spatial Query Pattern

```sql
-- ✅ CORRECT — Uses GIST index via geography cast
SELECT id, name FROM locations
WHERE ST_DWithin(
    coordinates::geography,
    ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)::geography,
    :radius_meters
);

-- ❌ FORBIDDEN — Full table scan, ignores GIST index
SELECT id, name FROM locations
WHERE ST_Distance(coordinates, ST_MakePoint(:lng, :lat)) < :radius_meters;
```

> ⚠️ The `::geography` cast is required for ST_DWithin to use metres on SRID 4326. Without it, the comparison uses degrees — producing a geofence that is the **wrong size** at Indonesian latitudes.

---

## 17. NON-FUNCTIONAL REQUIREMENTS

| Category | Requirement |
|----------|-------------|
| **Availability** | 99.5% uptime. Maintenance: Sunday 02:00–04:00 WIB. Unplanned downtime → admin email within 5 minutes. |
| **Data Durability** | Zero data loss on check-ins. PostgreSQL WAL enabled. Synchronous commit for attendance writes. |
| **Backup** | Daily automated PostgreSQL dump to S3. Point-in-time recovery (PITR) minimum 7 days. Test restores quarterly. |
| **Response Time** | Admin web pages < 2s first load. API endpoints < 1s P95. Check-in flow < 2s P95. |
| **Concurrency** | 500 simultaneous officer check-ins. 200 simultaneous admin users. No degradation. |
| **Browser (Admin)** | Chrome 110+, Firefox 110+, Safari 16+, Edge 110+. Dark mode mandatory. |
| **Browser (Officer)** | Chrome Mobile 90+, Safari Mobile 15+, Samsung Internet 14+. Camera API required. |
| **Localization** | Bahasa Indonesia default. Date: DD-MM-YYYY. Time: HH:MM:SS. Timezone: WIB (UTC+7). All timestamps stored as TIMESTAMPTZ (UTC internally). |
| **Accessibility** | WCAG 2.1 AA for critical admin workflows. Color is never the sole means of conveying status. |
| **Environment Parity** | Dev, staging, and production must all use PostgreSQL + PostGIS. SQLite is not acceptable in any environment. |
| **Code Quality** | PHP CS Fixer (PSR-12). PHPStan Level 6 minimum. Laravel Pint. No raw queries in application code. |
| **Secrets Management** | No secrets in code or `.env` files committed to VCS. Use Docker secrets, AWS Parameter Store, or equivalent. |

---

## 18. OPEN RISKS & ARCHITECTURAL DEBT REGISTER

| Risk ID | Description | Severity | Resolution |
|---------|-------------|----------|------------|
| R-01 | Coordinate mutability after first attendance | **RESOLVED** | `coords_locked = TRUE` after first attendance write. Read-only in UI. Admin must archive + create new location. |
| R-02 | PH overlap race condition under concurrent load | **HIGH — OPEN** | `pg_advisory_xact_lock` MUST be acquired in `AssignOfficerAction` before existence check. Partial unique index is second line of defence. Test T-03 is a release blocker. |
| R-03 | Client GPS as sole presence signal | **MEDIUM — ACCEPTED** | Multi-signal spoofing detection (Section 13.2). Accepted residual risk. Physical verification requires hardware (out of scope v1.0). |
| R-04 | Async watermark failure leaves check-in without verified photo | **MEDIUM — MITIGATED** | `photo_status` enum tracks state. Raw photo retained. Supervisor notified. Check-in remains valid. |
| R-05 | Borrowed officer data sovereignty | **MEDIUM — MITIGATED** | Cross-tenant read grant at assignment level (Section 2.3). |
| R-06 | Bypass token expiry too aggressive | **RESOLVED** | PH: 15 min. Patrol: 30 min. Escalation to God Admin at T+5 min (Section 11.3). |
| R-07 | No offline check-in for low-signal areas | **LOW — DEFERRED** | Out of scope v1.0. v1.1: offline queue with cryptographic timestamp proof + sync-on-reconnect. |
| R-08 | Recapitulation table scan at scale | **RESOLVED** | `daily_attendance_summary` materialized view refreshed nightly via pg_cron. |
| R-09 | Dashboard map polling overload | **MEDIUM — OPEN** | Rate limit: 1 req/30s per user. Redis cache-first. Cache miss → read replica. Load test must pass before production. |
| R-10 | Conflict between `operating_hours` and shift times | **RESOLVED** | `operating_hours` is **display-only**. Shifts are the sole enforcement window. UI tooltip: *"Jam operasional ini hanya sebagai referensi — jam shift yang menentukan absensi."* Final decision. |
| R-11 | Active assignments orphaned on operation archive | **RESOLVED** | **Option A selected.** Archive blocked if any assignment is `pending` or `active`. HTTP 422 with list of blocking assignments. Message: *"Operasi tidak dapat diarsipkan karena masih terdapat X penugasan aktif."* Admin must manually cancel before archiving. |

---

## 19. ENTITY RELATIONSHIP DIAGRAM (ERD)

### 19.1 Hierarchy Flow

```
┌─────────────────────────────────────────────────────────┐
│                        sakers                           │
│  id (PK)  name  code  type  parent_id (FK→self)         │
└─────────────────────┬───────────────────────────────────┘
                      │ 1:N (saker_id)
         ┌────────────┴────────────┐
         ▼                         ▼
┌────────────────┐        ┌─────────────────────┐
│     users      │        │     operations      │
│  id (PK)       │        │  id (PK)            │
│  saker_id (FK) │        │  saker_id (FK)      │
│  nrp UNIQUE    │        │  operation_type     │
│  role          │        │  status             │
│  safung        │        │  start_date/end_date│
└────────────────┘        └──────────┬──────────┘
                                     │ 1:N
                                     ▼
                          ┌─────────────────────┐
                          │        zones        │
                          │  id (PK)            │
                          │  operation_id (FK)  │
                          │  saker_id (FK)      │
                          └──────────┬──────────┘
                                     │ 1:N
                                     ▼
                ┌────────────────────────────────────────┐
                │              locations                 │
                │  id (PK)                               │
                │  zone_id (FK)    saker_id (FK)         │
                │  coordinates  GEOMETRY(POINT,4326)     │
                │  radius_meters   minimum_officer       │
                │  padal_id (FK)   coords_locked         │
                │  operating_hours JSONB [DISPLAY ONLY]  │
                └──────────┬─────────────────────────────┘
                           │ 1:N
                           ▼
                ┌─────────────────────┐
                │       shifts        │
                │  id (PK)            │
                │  location_id (FK)   │
                │  shift_start TIME   │
                │  shift_end TIME     │
                │  active_days[]      │
                └──────────┬──────────┘
                           │
       ┌───────────────────┼───────────────────┐
       │ officer (FK)      │                   │
       ▼                   ▼                   │
┌─────────────────────────────────────────┐   │
│             assignments                 │   │
│  id (PK)                                │   │
│  officer_id (FK)   location_id (FK)     │   │
│  shift_id (FK)     operation_id (FK)    │◄──┘
│  saker_id (FK)     assigned_saker_id(FK)│
│  assignment_date   status               │
│                                         │
│  UNIQUE(officer_id, date, shift_id)     │
│  WHERE status != 'cancelled'  [PH only] │
└──────────────────────┬──────────────────┘
                       │ 1:N (Patrol) / 1:1 max (PH)
                       ▼
┌──────────────────────────────────────────────────────┐
│               attendances  [IMMUTABLE]               │
│  id (PK)                                             │
│  assignment_id (FK)   officer_id (FK)                │
│  location_id (FK)     saker_id (FK)                  │
│  checkin_coordinates  GEOMETRY(POINT,4326)           │
│  distance_from_point  is_within_geofence             │
│  checked_in_at        is_within_shift                │
│  is_manual_bypass     bypass_approval_id (FK)        │
│  status               spoofing_score  spoofing_signals│
│  device_metadata JSONB   checksum VARCHAR(64)        │
│  photo_path   photo_raw_path   photo_status          │
│                                                      │
│  CREATE RULE no_update_attendances ... DO NOTHING    │
│  CREATE RULE no_delete_attendances ... DO NOTHING    │
└───────────┬──────────────────────┬───────────────────┘
            │ 1:0-1                │ 1:N
            ▼                     ▼
┌──────────────────────┐  ┌───────────────────────────┐
│ manual_bypass_       │  │  attendance_amendments    │
│ approvals            │  │  [IMMUTABLE]              │
│  id (PK)             │  │  id (PK)                  │
│  attendance_id (FK)  │  │  attendance_id (FK)       │
│  officer_id (FK)     │  │  amended_by (FK)          │
│  bypass_reason       │  │  reason TEXT required     │
│  status              │  │  field_changed            │
│  reviewed_by (FK)    │  │  old_value   new_value    │
│  signature_hmac      │  │  approved_by (FK)         │
│  expires_at          │  └───────────────────────────┘
└──────────────────────┘

─────────────────── SUPPORT TABLES ───────────────────

┌──────────────────────┐  ┌────────────────────────────────────┐
│    notifications     │  │          audit_logs [IMMUTABLE]    │
│  id (PK)             │  │  id (PK)                           │
│  recipient_id (FK)   │  │  actor_id (FK nullable)            │
│  saker_id (FK)       │  │  saker_id (FK nullable)            │
│  type                │  │  event_type    entity_type         │
│  title   body        │  │  entity_id (soft ref — no FK)      │
│  payload JSONB       │  │  payload_before JSONB              │
│  read_at             │  │  payload_after  JSONB              │
│  expires_at          │  │  actor_ip INET                     │
└──────────────────────┘  └────────────────────────────────────┘

─────────────── MATERIALIZED VIEW ────────────────────

┌────────────────────────────────────────────────────────────┐
│          daily_attendance_summary  [MAT. VIEW]             │
│  location_id  saker_id  zone_id  summary_date              │
│  total_checkins  minimum_officer  day_status               │
│  UNIQUE(location_id, summary_date)                         │
│  Refreshed nightly: pg_cron at 23:59 WIB                   │
└────────────────────────────────────────────────────────────┘
```

### 19.2 Relationship Cardinality Table

| Relationship | Cardinality | Constraint | Join Key |
|-------------|-------------|-----------|----------|
| sakers → users | 1 : N | `saker_id NOT NULL` | `sakers.id = users.saker_id` |
| sakers → operations | 1 : N | `saker_id NOT NULL` | `sakers.id = operations.saker_id` |
| operations → zones | 1 : N | `operation_id NOT NULL` | `operations.id = zones.operation_id` |
| zones → locations | 1 : N | `zone_id NOT NULL` | `zones.id = locations.zone_id` |
| locations → shifts | 1 : N | `location_id NOT NULL` | `locations.id = shifts.location_id` |
| users(officer) → assignments | 1:N (Patrol) / 1:1 per shift (PH) | Partial unique index on PH | `users.id = assignments.officer_id` |
| locations → assignments | 1 : N | `location_id NOT NULL` | `locations.id = assignments.location_id` |
| assignments → attendances | 1:N (Patrol) / 1:1 max (PH) | PH duplicate guard in app layer | `assignments.id = attendances.assignment_id` |
| attendances → manual_bypass_approvals | 1 : 0-1 | `bypass_approval_id` nullable FK | `attendances.bypass_approval_id = manual_bypass_approvals.id` |
| attendances → attendance_amendments | 1 : N | `attendance_id NOT NULL` | `attendances.id = attendance_amendments.attendance_id` |
| users → notifications | 1 : N | `recipient_id NOT NULL` | `users.id = notifications.recipient_id` |
| audit_logs | Polymorphic — all entities | `entity_type + entity_id` pair | No FK — soft reference for flexibility |
| daily_attendance_summary | Materialized view over assignments + attendances | `UNIQUE(location_id, summary_date)` | Refreshed nightly via pg_cron |

---

## 20. ARCHITECTURE CODE PATTERNS

> ⚠️ **These are not pseudocode.** They are the actual class structures the dev team must follow. Deviation requires CTO review.

### 20.1 Eloquent Model with SakerScope

```php
<?php
// app/Models/Concerns/SakerScope.php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SakerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // God Admin bypass flag set by SetGodAdminContext middleware
        if (app('saker.bypass')) {
            return;
        }

        $sakerId = auth()->user()?->saker_id
            ?? session('saker_id')
            ?? throw new \RuntimeException('Saker context not set');

        $builder->where($model->getTable() . '.saker_id', $sakerId);
    }
}
```

```php
<?php
// app/Models/Location.php
namespace App\Models;

use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([SakerScope::class])]
class Location extends Model
{
    protected $keyType = 'string';
    public $incrementing = false; // UUID primary key

    protected function boot(): void
    {
        parent::boot();
        // Auto-set UUID v7 on create
        static::creating(fn($m) => $m->id ??= \Ramsey\Uuid\Uuid::uuid7()->toString());
    }

    protected $casts = [
        'operating_hours' => 'array', // JSONB — display only
        'coords_locked'   => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function zone(): BelongsTo { return $this->belongsTo(Zone::class); }
    public function saker(): BelongsTo { return $this->belongsTo(Saker::class); }
    public function padal(): BelongsTo { return $this->belongsTo(User::class, 'padal_id'); }
    public function shifts(): HasMany { return $this->hasMany(Shift::class); }
    public function assignments(): HasMany { return $this->hasMany(Assignment::class); }
}
```

### 20.2 Repository Interface + Implementation

```php
<?php
// app/Repositories/Contracts/AttendanceRepositoryInterface.php
namespace App\Repositories\Contracts;

use App\Models\Attendance;

interface AttendanceRepositoryInterface
{
    public function createFromCheckin(array $data): Attendance;
    public function findByAssignment(string $assignmentId): ?Attendance;
    public function getCheckinCountForLocation(string $locationId, string $date): int;
    public function hasDuplicateCheckin(string $assignmentId): bool;
}
```

```php
<?php
// app/Repositories/AttendanceRepository.php
namespace App\Repositories;

use App\Models\Attendance;
use App\Repositories\Contracts\AttendanceRepositoryInterface;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function createFromCheckin(array $data): Attendance
    {
        // INSERT only — table has DB-level rule preventing UPDATE
        return Attendance::create($data);
    }

    public function hasDuplicateCheckin(string $assignmentId): bool
    {
        return Attendance::query()
            ->where('assignment_id', $assignmentId)
            ->whereIn('status', ['verified', 'flagged'])
            ->exists();
    }

    public function getCheckinCountForLocation(string $locationId, string $date): int
    {
        return Attendance::query()
            ->where('location_id', $locationId)
            ->whereDate('checked_in_at', $date)
            ->whereIn('status', ['verified', 'flagged'])
            ->count();
    }
}
```

### 20.3 GeofenceService

```php
<?php
// app/Services/GeofenceService.php
namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\DB;

class GeofenceService
{
    /**
     * Returns the distance in metres between submitted GPS and location point.
     * Uses PostGIS ST_DWithin via ::geography cast for metre-accurate comparison.
     */
    public function distanceFromLocation(
        Location $location,
        float $lat,
        float $lng
    ): float {
        $result = DB::selectOne('
            SELECT ST_Distance(
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                coordinates::geography
            ) AS distance_metres
            FROM locations WHERE id = ?
        ', [$lng, $lat, $location->id]);

        return round($result->distance_metres, 2);
    }

    public function isWithinGeofence(
        Location $location,
        float $lat,
        float $lng
    ): bool {
        $result = DB::selectOne('
            SELECT ST_DWithin(
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                coordinates::geography,
                ?
            ) AS within
            FROM locations WHERE id = ?
        ', [$lng, $lat, $location->radius_meters, $location->id]);

        return (bool) $result->within;
    }
}
```

### 20.4 Action Class — ProcessCheckinAction

```php
<?php
// app/Actions/ProcessCheckinAction.php
namespace App\Actions;

use App\DTOs\CheckinPayloadDTO;
use App\DTOs\CheckinResultDTO;
use App\Exceptions\CheckinException;
use App\Models\Assignment;
use App\Jobs\ProcessCheckinPhoto;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AuditService;
use App\Services\GeofenceService;
use App\Services\SpoofingDetectionService;
use Illuminate\Support\Facades\DB;

class ProcessCheckinAction
{
    public function __construct(
        private readonly GeofenceService              $geofenceService,
        private readonly SpoofingDetectionService     $spoofingService,
        private readonly AttendanceRepositoryInterface $attendanceRepo,
        private readonly AuditService                 $auditService,
    ) {}

    public function execute(
        Assignment $assignment,
        CheckinPayloadDTO $payload
    ): CheckinResultDTO {
        $location = $assignment->location;
        $now = now()->setTimezone('Asia/Jakarta');

        // ── Step 1: Shift window validation ─────────────────────────────
        $shiftStart = $this->resolveShiftTime($assignment, 'start');
        $shiftEnd   = $this->resolveShiftTime($assignment, 'end');

        if ($now->lt($shiftStart) || $now->gt($shiftEnd)) {
            $this->auditService->log('CHECKIN_REJECTED', $assignment, [
                'reason'       => 'OUTSIDE_SHIFT_WINDOW',
                'current_time' => $now->toIso8601String(),
            ]);
            throw CheckinException::outsideShiftWindow($shiftStart, $shiftEnd);
        }

        // ── Step 2: Mock location hard reject ────────────────────────────
        if ($payload->mockLocation === true) {
            $this->auditService->log('CHECKIN_REJECTED', $assignment, [
                'reason' => 'MOCK_LOCATION_DETECTED',
            ]);
            throw CheckinException::mockLocationDetected();
        }

        // ── Step 3: Geofence validation ──────────────────────────────────
        $distance    = $this->geofenceService->distanceFromLocation($location, $payload->latitude, $payload->longitude);
        $withinFence = $distance <= $location->radius_meters;

        if (! $withinFence) {
            $this->auditService->log('CHECKIN_REJECTED', $assignment, [
                'reason'   => 'OUTSIDE_GEOFENCE',
                'distance' => $distance,
                'radius'   => $location->radius_meters,
            ]);
            throw CheckinException::outsideGeofence($distance, $location->radius_meters);
        }

        // ── Step 4: PH duplicate guard ───────────────────────────────────
        if ($assignment->operation->operation_type === 'PH'
            && $this->attendanceRepo->hasDuplicateCheckin($assignment->id)) {
            throw CheckinException::alreadyCompleted($assignment->id);
        }

        // ── Step 5: Spoofing multi-signal scoring ────────────────────────
        $spoofing = $this->spoofingService->score($payload, $assignment->officer);
        if ($spoofing->score >= 2) {
            $this->auditService->log('CHECKIN_REJECTED', $assignment, [
                'reason'  => 'SPOOFING_SUSPECTED',
                'score'   => $spoofing->score,
                'signals' => $spoofing->signals,
            ]);
            throw CheckinException::spoofingSuspected($spoofing);
        }

        // ── Step 6: Atomic write ─────────────────────────────────────────
        $attendance = DB::transaction(function () use (
            $assignment, $payload, $distance, $withinFence, $shiftStart, $shiftEnd, $spoofing
        ) {
            $record = $this->attendanceRepo->createFromCheckin([
                'assignment_id'       => $assignment->id,
                'officer_id'          => $assignment->officer_id,
                'location_id'         => $assignment->location_id,
                'saker_id'            => $assignment->saker_id,
                'checkin_coordinates' => DB::raw("ST_SetSRID(ST_MakePoint({$payload->longitude},{$payload->latitude}),4326)"),
                'gps_accuracy_meters' => $payload->gpsAccuracy,
                'distance_from_point' => $distance,
                'is_within_geofence'  => $withinFence,
                'checked_in_at'       => now(),
                'shift_window_start'  => $shiftStart,
                'shift_window_end'    => $shiftEnd,
                'is_within_shift'     => true,
                'status'              => $spoofing->score === 1 ? 'flagged' : 'verified',
                'spoofing_score'      => $spoofing->score,
                'spoofing_signals'    => $spoofing->signals,
                'device_metadata'     => $payload->deviceMetadata,
                'checksum'            => $payload->checksum,
                'photo_status'        => 'pending',
            ]);

            $this->auditService->log('CHECKIN_SUBMITTED', $record, [
                'distance'       => $distance,
                'spoofing_score' => $spoofing->score,
            ]);

            return $record;
        });

        // Dispatch photo watermark job AFTER transaction commits
        ProcessCheckinPhoto::dispatch($attendance->id, $payload->photoPath);

        return new CheckinResultDTO(
            attendanceId:      $attendance->id,
            checkedInAt:       $attendance->checked_in_at,
            distanceFromPoint: $distance,
            isFlagged:         $spoofing->score === 1,
        );
    }
}
```

### 20.5 Controller — Thin by Design

```php
<?php
// app/Http/Controllers/Api/CheckinController.php
namespace App\Http\Controllers\Api;

use App\Actions\ProcessCheckinAction;
use App\DTOs\CheckinPayloadDTO;
use App\Http\Requests\Api\CheckinRequest;
use App\Models\Assignment;
use Illuminate\Http\JsonResponse;

class CheckinController extends Controller
{
    public function store(
        CheckinRequest $request,
        ProcessCheckinAction $action
    ): JsonResponse {
        $assignment = Assignment::findOrFail($request->assignment_id);

        // Authorization: officer must own this assignment
        $this->authorize('checkin', $assignment);

        $result = $action->execute(
            $assignment,
            CheckinPayloadDTO::fromRequest($request)
        );

        return response()->json([
            'status'              => 'success',
            'attendance_id'       => $result->attendanceId,
            'checked_in_at'       => $result->checkedInAt,
            'distance_from_point' => $result->distanceFromPoint,
            'is_flagged'          => $result->isFlagged,
            'photo_status'        => 'pending',
        ]);
    }
}
```

### 20.6 DTO (Data Transfer Object)

```php
<?php
// app/DTOs/CheckinPayloadDTO.php
namespace App\DTOs;

use App\Http\Requests\Api\CheckinRequest;
use Illuminate\Http\UploadedFile;

final class CheckinPayloadDTO
{
    public function __construct(
        public readonly float        $latitude,
        public readonly float        $longitude,
        public readonly float        $gpsAccuracy,
        public readonly string       $gpsProvider,
        public readonly bool         $mockLocation,
        public readonly string       $timestampDevice,
        public readonly string       $checksum,
        public readonly array        $deviceMetadata,
        public readonly UploadedFile $photo,
        public readonly ?string      $photoPath   = null,
        public readonly ?float       $gpsAltitude = null,
        public readonly ?float       $gpsSpeed    = null,
    ) {}

    public static function fromRequest(CheckinRequest $request): self
    {
        return new self(
            latitude:        (float) $request->latitude,
            longitude:       (float) $request->longitude,
            gpsAccuracy:     (float) $request->gps_accuracy,
            gpsProvider:     $request->gps_provider,
            mockLocation:    (bool)  $request->mock_location,
            timestampDevice: $request->timestamp_device,
            checksum:        $request->checksum,
            deviceMetadata:  $request->only(['os', 'os_version', 'app_version', 'model']),
            photo:           $request->file('photo'),
            gpsAltitude:     $request->gps_altitude ? (float) $request->gps_altitude : null,
            gpsSpeed:        $request->gps_speed    ? (float) $request->gps_speed    : null,
        );
    }
}
```

### 20.7 Service Provider — Binding Interfaces

```php
<?php
// app/Providers/RepositoryServiceProvider.php
namespace App\Providers;

use App\Repositories\AttendanceRepository;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AttendanceRepositoryInterface::class, AttendanceRepository::class);
        // Register all other repository bindings here:
        // $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        // $this->app->bind(AssignmentRepositoryInterface::class, AssignmentRepository::class);
    }
}
```

---

*End of Document — Version 2.1.0*  
*Next review: After schema finalization, before any feature code is written.*  
*All architectural decisions made after this point must be logged as amendments to this document.*

---

`INTERNAL — LAW ENFORCEMENT USE ONLY`  
*Systems Architecture Office — May 2026*
