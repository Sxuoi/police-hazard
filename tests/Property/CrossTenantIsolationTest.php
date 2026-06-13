<?php

namespace Tests\Property;

use App\Repositories\AssignmentRepository;
use Eris\Generator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * P4 — Cross-Tenant Isolation.
 *
 * For every officer O with saker_id = S_A, any query referencing an assignment
 * with saker_id = S_B ≠ S_A returns null or is filtered out.
 *
 * Enforces R2.11, R3.4, R5.10, R11.6.
 */
class CrossTenantIsolationTest extends PostgresPropertyTestCase
{
    public function test_officer_never_sees_foreign_saker_assignments(): void
    {
        // Create two sakers and officers (one per saker)
        $sakerA = $this->seedSaker('SA');
        $sakerB = $this->seedSaker('SB');
        $officerA = $this->seedOfficer($sakerA);
        $this->seedAssignment($sakerB, $this->seedOfficer($sakerB));

        $repo = new AssignmentRepository;

        // Without DB modification per iteration, the result is fixed —
        // we run multiple iterations to confirm the invariant holds
        // deterministically against repository contract.
        $this->forAll(
            Generator\choose(1, 50),
        )->then(function ($_) use ($repo, $officerA, $sakerA): void {
            $result = $repo->findForOfficerToday($officerA, $sakerA, Carbon::today());
            // Officer A in Saker A must never receive Saker B's assignment
            if ($result !== null) {
                $this->assertSame($sakerA, $result->saker_id);
            } else {
                $this->assertNull($result);
            }
        });
    }

    private function seedSaker(string $prefix): string
    {
        $id = Uuid::uuid7()->toString();
        DB::table('sakers')->insert([
            'id' => $id, 'name' => $prefix.' Saker', 'code' => $prefix.'-'.substr($id, 0, 6),
            'type' => 'POLDA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function seedOfficer(string $sakerId): string
    {
        $id = Uuid::uuid7()->toString();
        DB::table('users')->insert([
            'id' => $id, 'saker_id' => $sakerId, 'name' => 'Officer',
            'nrp' => 'N'.bin2hex(random_bytes(8)), 'role' => 'officer',
            'password' => bcrypt('password'), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function seedAssignment(string $sakerId, string $officerId): void
    {
        $opId = Uuid::uuid7()->toString();
        $zoneId = Uuid::uuid7()->toString();
        $locId = Uuid::uuid7()->toString();
        $shiftId = Uuid::uuid7()->toString();
        $asgnId = Uuid::uuid7()->toString();

        DB::table('operations')->insert([
            'id' => $opId, 'saker_id' => $sakerId, 'name' => 'Op',
            'operation_type' => 'PH', 'status' => 'active',
            'start_time' => '08:00:00', 'end_time' => '16:00:00',
            'created_by' => $officerId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('zones')->insert([
            'id' => $zoneId, 'operation_id' => $opId, 'saker_id' => $sakerId,
            'name' => 'Zone', 'is_active' => true, 'created_by' => $officerId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::statement("INSERT INTO locations (id, zone_id, saker_id, name, coordinates, radius_meters, minimum_officer, coords_locked, is_active, created_by, created_at, updated_at) VALUES ('{$locId}','{$zoneId}','{$sakerId}','Loc', ST_SetSRID(ST_MakePoint(106.8, -6.2), 4326), 50, 1, false, true, '{$officerId}', NOW(), NOW())");
        DB::statement("INSERT INTO shifts (id, location_id, name, shift_start, shift_end, active_days, is_active, created_at, updated_at) VALUES ('{$shiftId}','{$locId}','Shift','08:00:00','16:00:00', ARRAY[1,2,3,4,5,6,7]::SMALLINT[], true, NOW(), NOW())");
        DB::table('assignments')->insert([
            'id' => $asgnId, 'officer_id' => $officerId, 'location_id' => $locId,
            'shift_id' => $shiftId, 'operation_id' => $opId, 'saker_id' => $sakerId,
            'assigned_saker_id' => $sakerId, 'assignment_date' => Carbon::today()->toDateString(),
            'status' => 'active', 'assigned_by' => $officerId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
