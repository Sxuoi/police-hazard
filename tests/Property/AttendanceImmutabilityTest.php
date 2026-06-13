<?php

namespace Tests\Property;

use Eris\Generator;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * P1 — Attendance Immutability.
 *
 * For every attendance row A and every code path: subsequent reads return
 * A deep-equal except photo_path/photo_status (which may transition
 * pending → processed|failed at most once).
 *
 * Enforces R3.13, R3.14, R8.4.
 */
class AttendanceImmutabilityTest extends PostgresPropertyTestCase
{
    private string $sakerId;

    private string $officerId;

    private string $locationId;

    private string $assignmentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSupport();
    }

    public function test_distance_from_point_never_changes(): void
    {
        $this->forAll(
            Generator\float(0, 500),
        )->then(function (float $attemptedDistance): void {
            $attId = Uuid::uuid7()->toString();
            $original = 25.5;

            DB::statement("
                INSERT INTO attendances (
                    id, assignment_id, officer_id, location_id, saker_id,
                    distance_from_point, is_within_geofence, checked_in_at,
                    shift_window_start, shift_window_end, is_within_shift,
                    is_manual_bypass, status, spoofing_score, device_metadata,
                    photo_status, checksum, checkin_coordinates, created_at
                ) VALUES (
                    '{$attId}', '{$this->assignmentId}', '{$this->officerId}',
                    '{$this->locationId}', '{$this->sakerId}',
                    {$original}, true, NOW(), NOW(), NOW() + INTERVAL '8 hours',
                    true, false, 'verified', 0, '{}'::jsonb,
                    'pending', 'chk_test_".substr($attId, 0, 55)."', ST_SetSRID(ST_MakePoint(106.8, -6.2), 4326), NOW()
                )
            ");

            DB::table('attendances')->where('id', $attId)->update([
                'distance_from_point' => $attemptedDistance,
                'photo_status' => 'processed',
                'photo_path' => 'photos/test.jpg',
            ]);

            $row = DB::table('attendances')->where('id', $attId)->first();

            $this->assertEqualsWithDelta($original, (float) $row->distance_from_point, 0.01,
                'distance_from_point must never change (DB trigger rejects)');
        });
    }

    private function seedSupport(): void
    {
        $this->sakerId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
        $this->locationId = Uuid::uuid7()->toString();
        $this->assignmentId = Uuid::uuid7()->toString();

        $opId = Uuid::uuid7()->toString();
        $zoneId = Uuid::uuid7()->toString();
        $shiftId = Uuid::uuid7()->toString();

        DB::table('sakers')->insert(['id' => $this->sakerId, 'name' => 'S', 'code' => 'S-'.substr($this->sakerId, 0, 6), 'type' => 'POLDA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->insert(['id' => $this->officerId, 'saker_id' => $this->sakerId, 'name' => 'O', 'nrp' => 'NRP'.substr($this->officerId, 0, 8), 'role' => 'officer', 'password' => bcrypt('p'), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('operations')->insert(['id' => $opId, 'saker_id' => $this->sakerId, 'name' => 'Op', 'operation_type' => 'PH', 'status' => 'active', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'created_by' => $this->officerId, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('zones')->insert(['id' => $zoneId, 'operation_id' => $opId, 'saker_id' => $this->sakerId, 'name' => 'Z', 'is_active' => true, 'created_by' => $this->officerId, 'created_at' => now(), 'updated_at' => now()]);
        DB::statement("INSERT INTO locations (id, zone_id, saker_id, name, coordinates, radius_meters, minimum_officer, coords_locked, is_active, created_by, created_at, updated_at) VALUES ('{$this->locationId}','{$zoneId}','{$this->sakerId}','L', ST_SetSRID(ST_MakePoint(106.8, -6.2), 4326), 50, 1, false, true, '{$this->officerId}', NOW(), NOW())");
        DB::statement("INSERT INTO shifts (id, location_id, name, shift_start, shift_end, active_days, is_active, created_at, updated_at) VALUES ('{$shiftId}','{$this->locationId}','S','08:00:00','16:00:00', ARRAY[1,2,3,4,5,6,7]::SMALLINT[], true, NOW(), NOW())");
        DB::table('assignments')->insert(['id' => $this->assignmentId, 'officer_id' => $this->officerId, 'location_id' => $this->locationId, 'shift_id' => $shiftId, 'operation_id' => $opId, 'saker_id' => $this->sakerId, 'assigned_saker_id' => $this->sakerId, 'assignment_date' => now()->toDateString(), 'status' => 'active', 'assigned_by' => $this->officerId, 'created_at' => now(), 'updated_at' => now()]);
    }
}
