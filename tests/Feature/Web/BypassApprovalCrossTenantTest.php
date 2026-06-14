<?php

namespace Tests\Feature\Web;

use App\Models\ManualBypassApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class BypassApprovalCrossTenantTest extends TestCase
{
    use RefreshDatabase;

    private string $sakerAId;

    private string $sakerBId;

    private User $sakerBAdmin;

    private string $officerId;

    private string $assignmentId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires Postgres + PostGIS.');
        }

        $this->sakerAId = Uuid::uuid7()->toString();
        $this->sakerBId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
        $this->assignmentId = Uuid::uuid7()->toString();

        $adminAId = Uuid::uuid7()->toString();
        $operationId = Uuid::uuid7()->toString();
        $zoneId = Uuid::uuid7()->toString();
        $locationId = Uuid::uuid7()->toString();

        // Saker A — owns the bypass
        DB::table('sakers')->insert([
            'id' => $this->sakerAId,
            'name' => 'Saker A',
            'code' => 'SA-'.substr($this->sakerAId, 0, 6),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Saker B — the admin trying to approve cross-tenant
        DB::table('sakers')->insert([
            'id' => $this->sakerBId,
            'name' => 'Saker B',
            'code' => 'SB-'.substr($this->sakerBId, 0, 6),
            'type' => 'POLRESTABES',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sakerBAdmin = User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerBId,
            'name' => 'Saker B Admin',
            'nrp' => 'SB'.rand(1000, 9999),
            'role' => 'saker_admin',
            'password' => 'password',
            'is_active' => true,
        ]);

        // Admin A (for created_by FK)
        DB::table('users')->insert([
            'id' => $adminAId,
            'saker_id' => $this->sakerAId,
            'name' => 'Admin A',
            'nrp' => 'AA'.rand(1000, 9999),
            'role' => 'saker_admin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => $this->officerId,
            'saker_id' => $this->sakerAId,
            'name' => 'Officer Saker A',
            'nrp' => 'OA'.rand(1000, 9999),
            'role' => 'officer',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('operations')->insert([
            'id' => $operationId,
            'saker_id' => $this->sakerAId,
            'name' => 'Test Op A',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
            'created_by' => $adminAId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insert([
            'id' => $zoneId,
            'operation_id' => $operationId,
            'saker_id' => $this->sakerAId,
            'name' => 'Zone A',
            'is_active' => true,
            'created_by' => $adminAId,
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
                '{$this->sakerAId}',
                'Location A',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$adminAId}',
                NOW(), NOW(), 'Asia/Jakarta'
            )
        ");

        DB::table('assignments')->insert([
            'id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'location_id' => $locationId,
            'operation_id' => $operationId,
            'saker_id' => $this->sakerAId,
            'assigned_saker_id' => $this->sakerAId,
            'start_date' => Carbon::today()->toDateString(),
            'status' => 'active',
            'assigned_by' => $adminAId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_saker_admin_cannot_approve_another_sakers_bypass(): void
    {
        $bypass = ManualBypassApproval::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerAId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'GPS tidak akurat di lokasi ini.',
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'officer_latitude' => -6.2088,
            'officer_longitude' => 106.8456,
            'officer_gps_accuracy' => 15.0,
            'officer_device_metadata' => json_encode(['platform' => 'android']),
        ]);

        // Saker B admin cannot approve Saker A's bypass.
        // The SakerScope + findPendingForUpdate prevents cross-tenant access,
        // resulting in a 404 (ModelNotFoundException).
        $response = $this->actingAs($this->sakerBAdmin)->post(
            route('bypass-approvals.approve', $bypass->id),
            ['reviewer_note' => 'Saya menyetujui bypass ini karena alasan yang valid.']
        );

        $response->assertStatus(404);

        // Bypass should still be pending
        $bypass->refresh();
        $this->assertEquals('pending', $bypass->status);
    }

    public function test_saker_admin_cannot_deny_another_sakers_bypass(): void
    {
        $bypass = ManualBypassApproval::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'assignment_id' => $this->assignmentId,
            'officer_id' => $this->officerId,
            'saker_id' => $this->sakerAId,
            'bypass_reason' => 'OUTSIDE_GEOFENCE',
            'officer_note' => 'GPS tidak akurat di lokasi ini.',
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'officer_latitude' => -6.2088,
            'officer_longitude' => 106.8456,
            'officer_gps_accuracy' => 15.0,
            'officer_device_metadata' => json_encode(['platform' => 'android']),
        ]);

        // Saker B admin cannot deny Saker A's bypass.
        $response = $this->actingAs($this->sakerBAdmin)->post(
            route('bypass-approvals.deny', $bypass->id),
            ['reviewer_note' => 'Saya menolak bypass ini karena alasan yang valid.']
        );

        $response->assertStatus(404);

        $bypass->refresh();
        $this->assertEquals('pending', $bypass->status);
    }
}
