# Decision Record: Phase 3 Crypto Simplification

**Date:** 2026-05-12
**Status:** Accepted
**Context:** Phase 3 — Mobile Officer Check-In

## Summary

Phase 3 implements the mobile officer check-in system with a deliberately simplified security model. Several cryptographic and device-binding features from the original PRD were evaluated and intentionally dropped to reduce complexity, attack surface, and maintenance burden without materially weakening the system's integrity guarantees.

## Scope of Phase 3

Phase 3 delivers:
- Officer mobile authentication via Sanctum PAT (NRP + password)
- GPS-verified, photo-documented check-in with server-side watermarking
- Multi-signal spoofing detection (server-side scoring)
- Manual bypass workflow with supervisor approval, escalation, and expiration
- Immutable audit trail for all state-changing operations
- Server-internal attendance checksum (SHA-256, computed server-side)
- RFC 7807 error responses with reason codes and bypass eligibility

## Dropped Features and Rationale

### 1. No Device Binding / No `device_id`

**What was proposed:** Bind Sanctum tokens to a device fingerprint (User-Agent hash, screen resolution, etc.) and reject requests from mismatched devices.

**Why dropped:**
- Browser fingerprinting is unreliable (UA changes on updates, incognito mode, etc.)
- Adds false-positive friction for legitimate officers switching browsers
- Does not prevent a determined attacker who can replay the same fingerprint
- Token expiry (12h) + session-only storage already limits exposure window

### 2. No JWT Bypass Tokens

**What was proposed:** Issue a signed JWT to the officer after a check-in rejection, which they present with the bypass request to prove the rejection was genuine.

**Why dropped:**
- The bypass request already stores the full officer GPS/photo bundle server-side
- The supervisor reviews the same data that caused the rejection — no need for a client-held proof
- JWTs add key management complexity and a new attack vector (token theft/replay)
- The server already knows the rejection happened (audit trail)

### 3. No Client-Submitted Checksum

**What was proposed:** The mobile client computes a SHA-256 checksum over the check-in payload and submits it alongside the data for tamper detection.

**Why dropped:**
- A client-computed checksum provides no security guarantee — an attacker who can modify the payload can also recompute the checksum
- The server-internal checksum (computed after all validation passes) is strictly stronger
- Reduces client-side complexity and eliminates a class of "checksum mismatch" false alarms

### 4. No HMAC-SHA256 on Supervisor Decisions

**What was proposed:** Supervisor approval/denial carries an HMAC signature using a per-user secret, stored on the bypass record for non-repudiation.

**Why dropped:**
- The audit trail already records `reviewed_by`, `reviewer_note`, `reviewed_at` with the reviewer's authenticated identity
- HMAC key management (rotation, storage, per-user secrets) adds operational complexity
- PostgreSQL's narrow-transition rules enforce that only `pending → approved/denied/expired` transitions are possible — the DB itself is the integrity guarantee
- Non-repudiation is already achieved via the immutable `audit_logs` table

## Trade-offs Accepted

- **Token theft:** If an attacker obtains a Sanctum token, they can impersonate the officer until expiry. Mitigation: 12h expiry, session-only storage, `RevokeOfficerTokensAction` on deactivation.
- **No offline proof of rejection:** The officer cannot independently prove a rejection happened without server access. Mitigation: the audit trail is authoritative and available to supervisors.
- **No cryptographic non-repudiation:** Supervisor decisions rely on application-level identity rather than cryptographic signatures. Mitigation: immutable audit logs + DB rules prevent post-hoc tampering.

## Future Considerations

If the threat model evolves (e.g., insider attacks on the database, regulatory requirements for cryptographic non-repudiation), these features can be revisited in a future phase. The current architecture does not preclude adding them later.
