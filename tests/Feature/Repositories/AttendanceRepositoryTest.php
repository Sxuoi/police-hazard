<?php

namespace Tests\Feature\Repositories;

use App\Repositories\AttendanceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * Feature tests for AttendanceRepository — Phase 3 methods.
 * Runs against Postgres; skips on other drivers.
 */
class AttendanceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceRepository $repo;

    private string $sakerId;

    private string $officerId;

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

        $this->repo = new AttendanceRepository;
        $this->seedSupportingRecords();
    }

    // ── Tests ────────────────────────────────────────────────────────

    public function test_verified_exists_for_returns_true_when_verified_attendance_exists(): void
    {
        $attendanceId = Uuid::uuid7()->toString();

        $this->insertAttendance($attendanceId, 'verified');

        $result = $this->repo->verifiedExistsFor($this->assignmentId);

        $this->assertTrue($result);
    }

    public function test_verified_exists_for_returns_false_when_no_attendance(): void
    {
        $result = $this->repo->verifiedExistsFor($this->assignmentId);

        $this->assertFalse($result);
    }

    public function test_mark_photo_processed_updates_photo_fields(): void
    {
        $attendanceId = Uuid::uuid7()->toString();

        $this->insertAttendance($attendanceId, 'verified', 'pending');

        $s3Key = 'photos/processed/'.$attendanceId.'.jpg';
        $this->repo->markPhotoProcessed($attendanceId, $s3Key);

        $row = DB::table('attendances')->where('id', $attendanceId)->first();

        $this->assertSame('processed', $row->photo_status);
        $this->assertSame($s3Key, $row->photo_path);
    }

    public function test_mark_photo_failed_updates_status(): void
    {
        $attendanceId = Uuid::uuid7()->toString();

        $this->insertAttendance($attendanceId, 'verified', 'pending');

        $this->repo->markPhotoFailed($attendanceId);

        $row = DB::table('attendances')->where('id', $attendanceId)->first();

        $this->assertSame('failed', $row->photo_status);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function insertAttendance(
        string $attendanceId,
        string $status = 'verified',
        string $photoStatus = 'pending',
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
                '{$this->officerId}',
                '{$this->locationId}',
                '{$this->sakerId}',
                10.5,
                true,
                NOW(), NOW(), NOW() + INTERVAL '8 hours',
                true, false,
                '{$status}', 0, '{}',
                '{$photoStatus}',
                'checksum_placeholder_64chars_padding_to_fill_the_field_properly_',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                NOW()
            )
        ");
    }

    private function seedSupportingRecords(): void
    {
        $this->sakerId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
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
