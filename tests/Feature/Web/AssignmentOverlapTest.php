<?php

namespace Tests\Feature\Web;

use App\Actions\AssignOfficerToLocationAction;
use App\Models\Location;
use App\Models\Operation;
use App\Models\Saker;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class AssignmentOverlapTest extends TestCase
{
    use RefreshDatabase;

    private Saker $sakerA;
    private Saker $sakerB;
    private User $officer;
    private Location $locationA;
    private Location $locationB;
    private Operation $operationA;
    private Operation $operationB;
    private AssignOfficerToLocationAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires Postgres + PostGIS.');
        }

        $this->action = app(AssignOfficerToLocationAction::class);

        // Create two Sakers (tenants)
        $this->sakerA = Saker::create([
            'id' => Uuid::uuid7()->toString(),
            'name' => 'Saker A',
            'code' => 'SAK-A',
            'type' => 'POLRESTABES',
            'email' => 'sakera@gmail.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->sakerB = Saker::create([
            'id' => Uuid::uuid7()->toString(),
            'name' => 'Saker B',
            'code' => 'SAK-B',
            'type' => 'POLRESTABES',
            'email' => 'sakerb@gmail.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Create an officer belonging to Saker A
        $this->officer = User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerA->id,
            'name' => 'Officer A',
            'nrp' => 'OF9999',
            'role' => 'officer',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Create operations of type PH
        $opAId = Uuid::uuid7()->toString();
        DB::table('operations')->insert([
            'id' => $opAId,
            'saker_id' => $this->sakerA->id,
            'name' => 'PH Operation A',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'created_by' => $this->officer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->operationA = Operation::withoutGlobalScopes()->find($opAId);

        $opBId = Uuid::uuid7()->toString();
        DB::table('operations')->insert([
            'id' => $opBId,
            'saker_id' => $this->sakerB->id,
            'name' => 'PH Operation B',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '13:00:00', // Non-colliding time
            'end_time' => '17:00:00',
            'created_by' => $this->officer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->operationB = Operation::withoutGlobalScopes()->find($opBId);

        // Create zones
        $zoneAId = Uuid::uuid7()->toString();
        DB::table('zones')->insert([
            'id' => $zoneAId,
            'operation_id' => $this->operationA->id,
            'saker_id' => $this->sakerA->id,
            'name' => 'Zone A',
            'is_active' => true,
            'created_by' => $this->officer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneBId = Uuid::uuid7()->toString();
        DB::table('zones')->insert([
            'id' => $zoneBId,
            'operation_id' => $this->operationB->id,
            'saker_id' => $this->sakerB->id,
            'name' => 'Zone B',
            'is_active' => true,
            'created_by' => $this->officer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create locations
        $locAId = Uuid::uuid7()->toString();
        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, timezone
            ) VALUES (
                '{$locAId}',
                '{$zoneAId}',
                '{$this->sakerA->id}',
                'Location A',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->officer->id}',
                'Asia/Jakarta'
            )
        ");
        $this->locationA = Location::withoutGlobalScopes()->where('id', $locAId)->firstOrFail();

        $locBId = Uuid::uuid7()->toString();
        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, timezone
            ) VALUES (
                '{$locBId}',
                '{$zoneBId}',
                '{$this->sakerB->id}',
                'Location B',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->officer->id}',
                'Asia/Jakarta'
            )
        ");
        $this->locationB = Location::withoutGlobalScopes()->where('id', $locBId)->firstOrFail();
    }

    public function test_cannot_assign_same_officer_to_overlapping_ph_within_same_saker(): void
    {
        $today = Carbon::today()->toDateString();

        // Log in as Saker A to simulate their context
        auth()->login($this->sakerA);

        // 1. Assign first location
        $this->action->execute([
            'officer_id' => $this->officer->id,
            'location_id' => $this->locationA->id,
            'operation_id' => $this->operationA->id,
            'saker_id' => $this->sakerA->id,
            'assigned_saker_id' => $this->sakerA->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerA);

        // 2. Try to assign to another location under the same Saker (operation A) on the same date.
        // This should fail with ValidationException.
        $this->expectException(ValidationException::class);
        
        $this->action->execute([
            'officer_id' => $this->officer->id,
            'location_id' => $this->locationA->id,
            'operation_id' => $this->operationA->id,
            'saker_id' => $this->sakerA->id,
            'assigned_saker_id' => $this->sakerA->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerA);
    }

    public function test_cannot_assign_same_officer_to_overlapping_ph_cross_tenant_saker(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Assign to Saker A
        auth()->login($this->sakerA);
        $this->action->execute([
            'officer_id' => $this->officer->id,
            'location_id' => $this->locationA->id,
            'operation_id' => $this->operationA->id,
            'saker_id' => $this->sakerA->id,
            'assigned_saker_id' => $this->sakerA->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerA);

        // 2. Try to assign to Saker B (cross-tenant).
        // Since we are acting as Saker B admin, under the current implementation (bug),
        // Saker B doesn't see Saker A's assignment operation, and allows it.
        // Under the fixed implementation, this should throw ValidationException.
        auth()->login($this->sakerB);
        $this->expectException(ValidationException::class);

        $this->action->execute([
            'officer_id' => $this->officer->id,
            'location_id' => $this->locationB->id,
            'operation_id' => $this->operationB->id,
            'saker_id' => $this->sakerB->id,
            'assigned_saker_id' => $this->sakerB->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerB);
    }
}
