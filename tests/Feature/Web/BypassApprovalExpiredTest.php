<?php

namespace Tests\Feature\Web;

use App\Models\ManualBypassApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class BypassApprovalExpiredTest extends TestCase
{
    use RefreshDatabase;

    private string $sakerId;

    private string $officerId;

    private string $assignmentId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires Postgres + PostGIS.');
        }

        $this->sakerId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
        $this->assignmentId = Uuid::uuid7()->toString();

        $operationId = Uuid::uuid7()->toString();
        $zoneId = Uuid::uuid7()->toString();
        $locationId = Uuid::uuid7()->toString();
        $shiftId = Uuid::uuid7()->toString();

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
            'id' => $operationId,
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
            'id' => $zoneId,
            'operation_id' => $operationId,
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
                '{$locationId}',
                '{$zoneId}',
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
                '{$shiftId}',
                '{$locationId}',
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
            'location_id' => $locationId,
            'shift_id' => $shiftId,
            'operation_id' => $operationId,
            'saker_id' => $this->sakerId,
            'assigned_saker_id' => $this->sakerId,
            'assignment_date' => Carbon::today()->toDateString(),
            'status' => 'active',
            'assigned_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_approving_expired_bypass_returns_error(): void
    {
        $bypass = ManualBypassApproval::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'GPS tidak akurat di lokasi ini.',
            'status' => 'pending',
            'expires_at' => now()->subMinutes(5), // Already expired
            'created_at' => now()->subMinutes(20),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('bypass-approvals.approve', $bypass->id),
            ['reviewer_note' => 'Saya menyetujui bypass ini karena alasan yang valid.']
        );

        $response->assertRedirect(route('bypass-approvals.show', $bypass->id));
        $response->assertSessionHas('error');
    }

    public function test_denying_expired_bypass_returns_error(): void
    {
        $bypass = ManualBypassApproval::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'GPS tidak akurat di lokasi ini.',
            'status' => 'pending',
            'expires_at' => now()->subMinutes(5), // Already expired
            'created_at' => now()->subMinutes(20),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('bypass-approvals.deny', $bypass->id),
            ['reviewer_note' => 'Saya menolak bypass ini karena alasan yang valid sekali.']
        );

        $response->assertRedirect(route('bypass-approvals.show', $bypass->id));
        $response->assertSessionHas('error');
    }
}
