<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * Phase 3 Migration Tests — verifies the narrow DB rules introduced in:
 *   - 2026_05_12_000002_extend_manual_bypass_approvals_for_phase3.php
 *   - 2026_05_12_000003_allow_narrow_photo_update_on_attendances.php
 *
 * All tests skip on non-Postgres connections because the rules are PG-specific.
 * Uses RefreshDatabase to ensure a clean schema state.
 */
class Phase3MigrationTest extends TestCase
{
    use RefreshDatabase;

    // ── Supporting record IDs ────────────────────────────────────────

    private string $sakerId;

    private string $userId;

    private string $operationId;

    private string $zoneId;

    private string $locationId;

    private string $assignmentId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Postgres-only');
        }

        $this->seedSupportingRecords();
    }

    // ── Test: manual_bypass_approvals pending → approved transition ──

    /**
     * A pending bypass can be transitioned to approved by updating only the
     * mutable reviewer fields while keeping immutable columns identical.
     */
    public function test_bypass_approval_can_transition_pending_to_approved(): void
    {
        $bypassId = Uuid::uuid7()->toString();

        DB::table('manual_bypass_approvals')->insert([
            'id' => $bypassId,
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->userId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'I was at the correct location but GPS drifted.',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ]);

        // Transition to approved — keep all immutable columns identical
        DB::table('manual_bypass_approvals')
            ->where('id', $bypassId)
            ->update([
                'status' => 'approved',
                'reviewed_by' => $this->userId,
                'reviewer_note' => 'Approved after reviewing GPS evidence.',
                'reviewed_at' => now()->toIso8601String(),
            ]);

        $row = DB::table('manual_bypass_approvals')->where('id', $bypassId)->first();

        $this->assertSame('approved', $row->status);
        $this->assertSame($this->userId, $row->reviewed_by);
        $this->assertNotNull($row->reviewer_note);
        $this->assertNotNull($row->reviewed_at);
    }

    // ── Test: bypass immutable columns cannot change after approval ──

    /**
     * After transitioning a bypass to approved, attempting to modify an
     * immutable column (officer_note, bypass_reason) is silently rejected — no-op.
     */
    public function test_bypass_approval_immutable_columns_cannot_change(): void
    {
        $bypassId = Uuid::uuid7()->toString();
        $originalNote = 'I was at the correct location but GPS drifted.';
        $originalReason = 'OUTSIDE_GEOFENCE';

        DB::table('manual_bypass_approvals')->insert([
            'id' => $bypassId,
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->userId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => $originalReason,
            'officer_note' => $originalNote,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ]);

        // First, legitimately transition to approved
        DB::table('manual_bypass_approvals')
            ->where('id', $bypassId)
            ->update([
                'status' => 'approved',
                'reviewed_by' => $this->userId,
                'reviewer_note' => 'Approved.',
                'reviewed_at' => now()->toIso8601String(),
            ]);

        // Now attempt to modify officer_note on the approved row — must be no-op
        DB::table('manual_bypass_approvals')
            ->where('id', $bypassId)
            ->update([
                'officer_note' => 'Tampered note after approval.',
            ]);

        $row = DB::table('manual_bypass_approvals')->where('id', $bypassId)->first();

        $this->assertSame(
            $originalNote,
            $row->officer_note,
            'officer_note on an approved row must not be modifiable (reject rule no-op)'
        );

        // Also attempt to modify bypass_reason — must be no-op
        DB::table('manual_bypass_approvals')
            ->where('id', $bypassId)
            ->update([
                'bypass_reason' => 'OUTSIDE_SHIFT_WINDOW',
            ]);

        $row = DB::table('manual_bypass_approvals')->where('id', $bypassId)->first();

        $this->assertSame(
            $originalReason,
            $row->bypass_reason,
            'bypass_reason on an approved row must not be modifiable (reject rule no-op)'
        );
    }

    // ── Test: attendance photo_status pending → processed transition ─

    /**
     * photo_status can transition from pending → processed, and photo_path
     * can be set at the same time when all other columns remain identical.
     */
    public function test_attendance_photo_status_can_transition_pending_to_processed(): void
    {
        $attendanceId = Uuid::uuid7()->toString();

        $this->insertAttendance($attendanceId, 'pending');

        // Update photo_path and photo_status together — narrow rule allows this
        DB::table('attendances')
            ->where('id', $attendanceId)
            ->update([
                'photo_path' => 'photos/'.$attendanceId.'.jpg',
                'photo_status' => 'processed',
            ]);

        $row = DB::table('attendances')->where('id', $attendanceId)->first();

        $this->assertSame('processed', $row->photo_status);
        $this->assertSame('photos/'.$attendanceId.'.jpg', $row->photo_path);
    }

    // ── Test: attendance immutable columns reject update via photo ───

    /**
     * Attempting to modify distance_from_point along with photo_status='processed'
     * is silently rejected by the narrow rule — the rule only allows photo_path
     * and photo_status to change, so the entire update is a no-op.
     */
    public function test_attendance_immutable_columns_cannot_change_via_photo_update(): void
    {
        $attendanceId = Uuid::uuid7()->toString();
        $originalDistance = 12.50;

        $this->insertAttendance($attendanceId, 'pending', $originalDistance);

        // Attempt to modify distance_from_point along with photo_status transition
        // The narrow rule requires distance_from_point to remain identical,
        // so this entire update is silently rejected (DO INSTEAD NOTHING)
        DB::table('attendances')
            ->where('id', $attendanceId)
            ->update([
                'distance_from_point' => 999.99,
                'photo_path' => 'photos/'.$attendanceId.'.jpg',
                'photo_status' => 'processed',
            ]);

        $row = DB::table('attendances')->where('id', $attendanceId)->first();

        $this->assertEqualsWithDelta(
            $originalDistance,
            (float) $row->distance_from_point,
            0.01,
            'distance_from_point is immutable — the DB rule must silently reject the update'
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Insert a minimal attendance row using raw SQL (PostGIS geometry required).
     */
    private function insertAttendance(
        string $attendanceId,
        string $photoStatus = 'pending',
        float $distanceFromPoint = 12.50,
    ): void {
        DB::statement("
            INSERT INTO attendances (
                id, assignment_id, officer_id, location_id, saker_id,
                distance_from_point, is_within_geofence,
                checked_in_at, shift_window_start, shift_window_end,
                is_within_shift, is_manual_bypass,
                status, spoofing_score, device_metadata,
                photo_status, checksum, checkin_coordinates, created_at
            ) VALUES (
                '{$attendanceId}',
                '{$this->assignmentId}',
                '{$this->userId}',
                '{$this->locationId}',
                '{$this->sakerId}',
                {$distanceFromPoint},
                true,
                NOW(), NOW(), NOW() + INTERVAL '8 hours',
                true, false,
                'verified', 0, '{}',
                '{$photoStatus}',
                'abc123checksum64chars_padding_to_fill_the_field_properly_ok_now',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                NOW()
            )
        ");
    }

    /**
     * Seed the minimal hierarchy of supporting records needed by the tests.
     * Uses raw DB inserts to bypass SakerScope and model events.
     */
    private function seedSupportingRecords(): void
    {
        $this->sakerId = Uuid::uuid7()->toString();
        $this->userId = Uuid::uuid7()->toString();
        $this->operationId = Uuid::uuid7()->toString();
        $this->zoneId = Uuid::uuid7()->toString();
        $this->locationId = Uuid::uuid7()->toString();
        $this->assignmentId = Uuid::uuid7()->toString();

        // 1. Saker
        DB::table('sakers')->insert([
            'id' => $this->sakerId,
            'name' => 'Test Polda',
            'code' => 'TEST-'.substr($this->sakerId, 0, 8),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // 2. User (officer)
        DB::table('users')->insert([
            'id' => $this->userId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Officer',
            'nrp' => 'NRP'.substr($this->sakerId, 0, 10),
            'role' => 'officer',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // 3. Operation
        DB::table('operations')->insert([
            'id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Operation',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'created_by' => $this->userId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // 4. Zone
        DB::table('zones')->insert([
            'id' => $this->zoneId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Zone',
            'is_active' => true,
            'created_by' => $this->userId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // 5. Location (with PostGIS coordinates — Jakarta area)
        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, created_at, updated_at
            ) VALUES (
                '{$this->locationId}',
                '{$this->zoneId}',
                '{$this->sakerId}',
                'Test Location',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->userId}',
                NOW(), NOW()
            )
        ");

        // 7. Assignment
        DB::table('assignments')->insert([
            'id' => $this->assignmentId,
            'officer_id' => $this->userId,
            'location_id' => $this->locationId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'assigned_by' => $this->userId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
