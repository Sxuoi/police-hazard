<?php

namespace Tests\Feature\Repositories;

use App\Repositories\AssignmentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * Feature tests for AssignmentRepository — Phase 3 methods.
 * Runs against Postgres; skips on other drivers.
 */
class AssignmentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AssignmentRepository $repo;

    private string $sakerId;

    private string $officerId;

    private string $operationId;

    private string $zoneId;

    private string $locationId;

    private string $shiftId;

    private string $shiftId2;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Postgres-only test');
        }

        $this->repo = new AssignmentRepository;
        $this->seedSupportingRecords();
    }

    // ── Tests ────────────────────────────────────────────────────────

    public function test_find_for_officer_today_returns_active_assignment(): void
    {
        $assignmentId = Uuid::uuid7()->toString();

        DB::table('assignments')->insert([
            'id' => $assignmentId,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId,
            'shift_id' => $this->shiftId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'assignment_date' => Carbon::today()->toDateString(),
            'status' => 'active',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $result = $this->repo->findForOfficerToday($this->officerId, $this->sakerId);

        $this->assertNotNull($result);
        $this->assertSame($assignmentId, $result->id);
    }

    public function test_find_for_officer_today_returns_null_for_cancelled(): void
    {
        $assignmentId = Uuid::uuid7()->toString();

        DB::table('assignments')->insert([
            'id' => $assignmentId,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId,
            'shift_id' => $this->shiftId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'assignment_date' => Carbon::today()->toDateString(),
            'status' => 'cancelled',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $result = $this->repo->findForOfficerToday($this->officerId, $this->sakerId);

        $this->assertNull($result);
    }

    public function test_list_for_officer_excludes_cancelled_and_sorts_by_shift(): void
    {
        $from = Carbon::today();
        $to = Carbon::today();

        // Assignment with later shift (16:00–00:00)
        $assignmentLate = Uuid::uuid7()->toString();
        DB::table('assignments')->insert([
            'id' => $assignmentLate,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId,
            'shift_id' => $this->shiftId2, // 16:00 start
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'assignment_date' => Carbon::today()->toDateString(),
            'status' => 'active',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Assignment with earlier shift (08:00–16:00)
        $assignmentEarly = Uuid::uuid7()->toString();
        DB::table('assignments')->insert([
            'id' => $assignmentEarly,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId,
            'shift_id' => $this->shiftId, // 08:00 start
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'assignment_date' => Carbon::today()->toDateString(),
            'status' => 'active',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Cancelled assignment — should be excluded
        $assignmentCancelled = Uuid::uuid7()->toString();
        DB::table('assignments')->insert([
            'id' => $assignmentCancelled,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId,
            'shift_id' => $this->shiftId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'assignment_date' => Carbon::today()->toDateString(),
            'status' => 'cancelled',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $results = $this->repo->listForOfficer($this->officerId, $this->sakerId, $from, $to);

        // Should have 2 results (cancelled excluded)
        $this->assertCount(2, $results);

        // First result should be the earlier shift (08:00)
        $this->assertSame($assignmentEarly, $results->first()->id);
        // Second result should be the later shift (16:00)
        $this->assertSame($assignmentLate, $results->last()->id);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function seedSupportingRecords(): void
    {
        $this->sakerId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
        $this->operationId = Uuid::uuid7()->toString();
        $this->zoneId = Uuid::uuid7()->toString();
        $this->locationId = Uuid::uuid7()->toString();
        $this->shiftId = Uuid::uuid7()->toString();
        $this->shiftId2 = Uuid::uuid7()->toString();

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

        // Shift 1: Morning (08:00–16:00)
        DB::statement("
            INSERT INTO shifts (id, location_id, name, shift_start, shift_end, active_days, is_active, created_at, updated_at)
            VALUES (
                '{$this->shiftId}',
                '{$this->locationId}',
                'Morning Shift',
                '08:00:00',
                '16:00:00',
                ARRAY[1,2,3,4,5,6,7]::SMALLINT[],
                true,
                NOW(), NOW()
            )
        ");

        // Shift 2: Afternoon (16:00–23:59)
        DB::statement("
            INSERT INTO shifts (id, location_id, name, shift_start, shift_end, active_days, is_active, created_at, updated_at)
            VALUES (
                '{$this->shiftId2}',
                '{$this->locationId}',
                'Afternoon Shift',
                '16:00:00',
                '23:59:00',
                ARRAY[1,2,3,4,5,6,7]::SMALLINT[],
                true,
                NOW(), NOW()
            )
        ");
    }
}
