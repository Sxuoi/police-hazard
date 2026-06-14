<?php

namespace Tests\Feature\Repositories;

use App\Models\User;
use App\Repositories\ManualBypassApprovalRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * Feature tests for ManualBypassApprovalRepository — Phase 3 methods.
 * Runs against Postgres; skips on other drivers.
 */
class ManualBypassApprovalRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ManualBypassApprovalRepository $repo;

    private string $sakerId;

    private string $officerId;

    private string $reviewerId;

    private string $operationId;

    private string $zoneId;

    private string $locationId;

    private string $assignmentId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Postgres-only test');
        }

        $this->repo = new ManualBypassApprovalRepository;
        $this->seedSupportingRecords();
    }

    // ── Tests ────────────────────────────────────────────────────────

    public function test_create_pending_sets_status_to_pending(): void
    {
        $bypass = $this->repo->createPending([
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'GPS drifted significantly during check-in attempt.',
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        $this->assertSame('pending', $bypass->status);
        $this->assertNotNull($bypass->id);

        $row = DB::table('manual_bypass_approvals')->where('id', $bypass->id)->first();
        $this->assertSame('pending', $row->status);
    }

    public function test_mark_approved_transitions_status(): void
    {
        $bypass = $this->repo->createPending([
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'GPS drifted significantly during check-in attempt.',
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        $reviewer = User::find($this->reviewerId);
        $this->repo->markApproved($bypass, $reviewer, 'Approved after GPS evidence review.');

        $row = DB::table('manual_bypass_approvals')->where('id', $bypass->id)->first();

        $this->assertSame('approved', $row->status);
        $this->assertSame($this->reviewerId, $row->reviewed_by);
        $this->assertNotNull($row->reviewed_at);
        $this->assertSame('Approved after GPS evidence review.', $row->reviewer_note);
    }

    public function test_mark_expired_transitions_status(): void
    {
        $bypass = $this->repo->createPending([
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_SHIFT_WINDOW',
            'officer_note' => 'Arrived slightly late due to traffic conditions.',
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        $this->repo->markExpired($bypass);

        $row = DB::table('manual_bypass_approvals')->where('id', $bypass->id)->first();

        $this->assertSame('expired', $row->status);
        $this->assertNotNull($row->reviewed_at);
    }

    public function test_advance_escalation_increments_level(): void
    {
        $bypass = $this->repo->createPending([
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'GPS drifted significantly during check-in attempt.',
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        $this->repo->advanceEscalation($bypass, 1);

        $row = DB::table('manual_bypass_approvals')->where('id', $bypass->id)->first();

        $this->assertSame(1, (int) $row->escalation_level);
    }

    public function test_list_expirable_returns_expired_pending_rows(): void
    {
        // Create a bypass that has already expired (expires_at in the past)
        $bypassExpired = $this->repo->createPending([
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'GPS drifted significantly during check-in attempt.',
            'expires_at' => now()->subMinutes(5),
            'created_at' => now()->subMinutes(20),
        ]);

        // Create a bypass that has NOT expired yet (expires_at in the future)
        $bypassNotExpired = $this->repo->createPending([
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_SHIFT_WINDOW',
            'officer_note' => 'Arrived slightly late due to traffic conditions.',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $expirable = $this->repo->listExpirable();

        $this->assertTrue($expirable->contains('id', $bypassExpired->id));
        $this->assertFalse($expirable->contains('id', $bypassNotExpired->id));
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function seedSupportingRecords(): void
    {
        $this->sakerId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
        $this->reviewerId = Uuid::uuid7()->toString();
        $this->operationId = Uuid::uuid7()->toString();
        $this->zoneId = Uuid::uuid7()->toString();
        $this->locationId = Uuid::uuid7()->toString();
        $this->assignmentId = Uuid::uuid7()->toString();

        DB::table('sakers')->insert([
            'id' => $this->sakerId,
            'name' => 'Test Polda',
            'code' => 'TEST-'.substr($this->sakerId, 0, 8),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        DB::table('users')->insert([
            'id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Officer',
            'nrp' => 'NRP'.substr($this->officerId, 0, 10),
            'role' => 'officer',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        DB::table('users')->insert([
            'id' => $this->reviewerId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Saker Admin',
            'nrp' => 'ADM'.substr($this->reviewerId, 0, 10),
            'role' => 'saker_admin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        DB::table('operations')->insert([
            'id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Operation',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'created_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        DB::table('zones')->insert([
            'id' => $this->zoneId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Zone',
            'is_active' => true,
            'created_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

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
                '{$this->officerId}',
                NOW(), NOW()
            )
        ");

        DB::table('assignments')->insert([
            'id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
