<?php

namespace Tests\Feature\Web;

use App\Models\ManualBypassApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class BypassApprovalDecideTest extends TestCase
{
    use RefreshDatabase;

    private string $sakerId;

    private string $officerId;

    private string $assignmentId;

    private string $operationId;

    private string $zoneId;

    private string $locationId;

    private string $shiftId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires Postgres + PostGIS.');
        }

        $this->sakerId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
        $this->operationId = Uuid::uuid7()->toString();
        $this->zoneId = Uuid::uuid7()->toString();
        $this->locationId = Uuid::uuid7()->toString();
        $this->shiftId = Uuid::uuid7()->toString();
        $this->assignmentId = Uuid::uuid7()->toString();

        DB::table('sakers')->insert([
            'id' => $this->sakerId,
            'name' => 'Test Saker',
            'code' => 'TS-'.substr($this->sakerId, 0, 6),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerId,
            'name' => 'Test God Admin',
            'nrp' => 'GA'.rand(1000, 9999),
            'role' => 'god_admin',
            'password' => 'password',
            'is_active' => true,
        ]);

        DB::table('users')->insert([
            'id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Officer',
            'nrp' => 'OF'.rand(1000, 9999),
            'role' => 'officer',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('operations')->insert([
            'id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Op',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
            'created_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insert([
            'id' => $this->zoneId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Zone',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, created_at, updated_at, timezone
            ) VALUES (
                '{$this->locationId}',
                '{$this->zoneId}',
                '{$this->sakerId}',
                'Test Location',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->admin->id}',
                NOW(), NOW(), 'Asia/Jakarta'
            )
        ");

        DB::statement("
            INSERT INTO shifts (id, location_id, name, shift_start, shift_end, active_days, is_active, created_at, updated_at)
            VALUES (
                '{$this->shiftId}',
                '{$this->locationId}',
                'Test Shift',
                '00:00:00',
                '23:59:00',
                ARRAY[1,2,3,4,5,6,7]::SMALLINT[],
                true,
                NOW(), NOW()
            )
        ");

        DB::table('assignments')->insert([
            'id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'location_id' => $this->locationId,
            'shift_id' => $this->shiftId,
            'operation_id' => $this->operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'assignment_date' => Carbon::today()->toDateString(),
            'status' => 'active',
            'assigned_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPendingBypass(array $overrides = []): ManualBypassApproval
    {
        $attrs = array_merge([
            'id' => Uuid::uuid7()->toString(),
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'Saya berada di dekat lokasi tetapi GPS tidak akurat.',
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'officer_latitude' => -6.2088,
            'officer_longitude' => 106.8456,
            'officer_gps_accuracy' => 15.0,
            'officer_device_metadata' => json_encode(['platform' => 'android', 'browser' => 'chrome']),
        ], $overrides);

        return ManualBypassApproval::withoutGlobalScopes()->create($attrs);
    }

    public function test_approve_with_valid_note_succeeds(): void
    {
        $bypass = $this->createPendingBypass();

        $response = $this->actingAs($this->admin)->post(
            route('bypass-approvals.approve', $bypass->id),
            ['reviewer_note' => 'Saya telah memverifikasi lokasi anggota dan menyetujui bypass ini.']
        );

        $response->assertRedirect(route('bypass-approvals.index'));
        $response->assertSessionHas('success');

        $bypass->refresh();
        $this->assertEquals('approved', $bypass->status);
    }

    public function test_deny_with_valid_note_succeeds(): void
    {
        $bypass = $this->createPendingBypass();

        $response = $this->actingAs($this->admin)->post(
            route('bypass-approvals.deny', $bypass->id),
            ['reviewer_note' => 'Lokasi anggota terlalu jauh dari titik penugasan, ditolak.']
        );

        $response->assertRedirect(route('bypass-approvals.index'));
        $response->assertSessionHas('success');

        $bypass->refresh();
        $this->assertEquals('denied', $bypass->status);
    }

    public function test_approve_with_short_note_fails_validation(): void
    {
        $bypass = $this->createPendingBypass();

        $response = $this->actingAs($this->admin)->post(
            route('bypass-approvals.approve', $bypass->id),
            ['reviewer_note' => 'terlalu pendek']
        );

        $response->assertSessionHasErrors('reviewer_note');
    }

    public function test_deny_with_short_note_fails_validation(): void
    {
        $bypass = $this->createPendingBypass();

        $response = $this->actingAs($this->admin)->post(
            route('bypass-approvals.deny', $bypass->id),
            ['reviewer_note' => 'pendek']
        );

        $response->assertSessionHasErrors('reviewer_note');
    }
}
