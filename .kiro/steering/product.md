# Police Hazard — Product Overview

Police Hazard (PH) is a web-based command-and-control attendance platform for Indonesian law enforcement agencies (Polri). It replaces unverifiable manual attendance with GPS-verified, photo-documented digital check-ins at static checkpoints (Operasi PH) and mobile patrol routes (Operasi Patroli).

## Core Capabilities

- Admin web UI (dark mode) for Saker Admins and God Admins to manage operations, zones, locations, officers, and assignments.
- Mobile-web check-in UI for officers using browser Geolocation + MediaDevices camera APIs.
- Server-side GPS geofencing via PostGIS `ST_DWithin`, multi-signal spoofing detection, and server-side photo watermarking.
- Real-time Leaflet/OSM dashboard with status-colored markers, plus God Admin heatmap across all Sakers.
- Immutable audit trails, recapitulation/export reporting, and a manual bypass workflow with supervisor approval.

## User Roles

- **God Admin** — global cross-Saker access; exclusive heatmap and tenant management.
- **Saker Admin** — manages own Saker's operations, zones, locations, and officers; approves bypasses.
- **Officer** (Anggota) — mobile check-in only; views own assignments and attendance history.

## Non-Negotiable Product Invariants

These rules are binding and must not be relaxed without explicit approval:

- **Tenant isolation (Saker):** enforced at three layers — PostgreSQL RLS, Eloquent `SakerScope`, and `EnsureSakerContext` middleware. Never weaken any layer.
- **Append-only records:** `attendances`, `attendance_amendments`, `manual_bypass_approvals`, and `audit_logs` are insert-only. DB rules reject UPDATE/DELETE. Never attempt to mutate them.
- **Location coordinate lock:** once any attendance exists for a location, `coordinates` become immutable (`coords_locked = true`). Corrections require archive + re-create.
- **PH (static) assignments:** one officer per location per shift per day; enforced by a partial unique index plus a PostgreSQL advisory lock in the assignment action.
- **Mock-location rejections are never bypassable.** All other geofence/shift rejections may offer a signed bypass token.
- **Timezone:** store UTC (`TIMESTAMPTZ`); display WIB (`Asia/Jakarta`, UTC+7). Midnight-spanning shifts count under `shift_start` date.
- **UUID v7 only** for primary keys (time-ordered). UUID v4 is prohibited.
