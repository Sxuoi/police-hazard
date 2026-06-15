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

    private string $operationId2;

    private string $zoneId;

    private string $locationId;

    private string $locationId2;

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
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'start_date' => Carbon::today()->toDateString(),
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
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'start_date' => Carbon::today()->toDateString(),
            'status' => 'cancelled',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $result = $this->repo->findForOfficerToday($this->officerId, $this->sakerId);

        $this->assertNull($result);
    }

    public function test_list_for_officer_excludes_cancelled_and_sorts_by_operation_time(): void
    {
        $from = Carbon::today();
        $to = Carbon::today();

        // Assignment with later operation (16:00–23:59)
        $assignmentLate = Uuid::uuid7()->toString();
        DB::table('assignments')->insert([
            'id' => $assignmentLate,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId,
            'operation_id' => $this->operationId2, // 16:00 start
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'start_date' => Carbon::today()->toDateString(),
            'status' => 'active',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Assignment with earlier operation (08:00–16:00)
        $assignmentEarly = Uuid::uuid7()->toString();
        DB::table('assignments')->insert([
            'id' => $assignmentEarly,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId2, // Use second location to avoid unique violation
            'operation_id' => $this->operationId, // 08:00 start
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'start_date' => Carbon::today()->toDateString(),
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
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'start_date' => Carbon::today()->toDateString(),
            'status' => 'cancelled',
            'assigned_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $results = $this->repo->listForOfficer($this->officerId, $this->sakerId, $from, $to);

        // Should have 2 results (cancelled excluded)
        $this->assertCount(2, $results);

        // First result should be the earlier operation (08:00)
        $this->assertSame($assignmentEarly, $results->first()->id);
        // Second result should be the later operation (16:00)
        $this->assertSame($assignmentLate, $results->last()->id);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function seedSupportingRecords(): void
    {
        $this->sakerId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
        $this->operationId = Uuid::uuid7()->toString();
        $this->operationId2 = Uuid::uuid7()->toString();
        $this->zoneId = Uuid::uuid7()->toString();
        $this->locationId = Uuid::uuid7()->toString();
        $this->locationId2 = Uuid::uuid7()->toString();

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
            'name' => 'Test Operation 1',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'created_by' => $this->officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        DB::table('operations')->insert([
            'id' => $this->operationId2,
            'saker_id' => $this->sakerId,
            'name' => 'Test Operation 2',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '16:00:00',
            'end_time' => '23:59:00',
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

        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, created_at, updated_at
            ) VALUES (
                '{$this->locationId2}',
                '{$this->zoneId}',
                '{$this->sakerId}',
                'Test Location 2',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->officerId}',
                NOW(), NOW()
            )
        ");
    }
}
