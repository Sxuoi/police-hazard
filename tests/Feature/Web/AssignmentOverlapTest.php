<?php

namespace Tests\Feature\Web;

use App\Actions\AssignOfficerToLocationAction;
use App\Models\Assignment;
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

    private Saker $sakerPolda;
    private Saker $sakerPolrestabes;
    private Saker $sakerPolsek;
    private Saker $sakerPolsekSibling;

    private User $officerPolsek;
    private User $officerPolda;

    private Operation $operationPolda;
    private Operation $operationPolsek;

    private Location $locationPolda;
    private Location $locationPolsek;

    private AssignOfficerToLocationAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires Postgres + PostGIS.');
        }

        $this->action = app(AssignOfficerToLocationAction::class);

        // 1. Create Saker Hierarchy: Polda -> Polrestabes -> Polsek
        $this->sakerPolda = Saker::create([
            'id' => Uuid::uuid7()->toString(),
            'name' => 'POLDA TEST',
            'code' => 'POLDA-TEST',
            'type' => 'POLDA',
            'email' => 'poldatest@gmail.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->sakerPolrestabes = Saker::create([
            'id' => Uuid::uuid7()->toString(),
            'name' => 'POLRESTABES TEST',
            'code' => 'PRTBS-TEST',
            'type' => 'POLRESTABES',
            'parent_id' => $this->sakerPolda->id,
            'email' => 'prtbstest@gmail.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->sakerPolsek = Saker::create([
            'id' => Uuid::uuid7()->toString(),
            'name' => 'POLSEK TEST A',
            'code' => 'PLSK-TEST-A',
            'type' => 'POLSEK',
            'parent_id' => $this->sakerPolrestabes->id,
            'email' => 'plsktesta@gmail.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->sakerPolsekSibling = Saker::create([
            'id' => Uuid::uuid7()->toString(),
            'name' => 'POLSEK TEST B',
            'code' => 'PLSK-TEST-B',
            'type' => 'POLSEK',
            'parent_id' => $this->sakerPolrestabes->id,
            'email' => 'plsktestb@gmail.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 2. Create Officers
        $this->officerPolsek = User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerPolsek->id,
            'name' => 'Officer Polsek',
            'nrp' => 'OF7777',
            'role' => 'officer',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->officerPolda = User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerPolda->id,
            'name' => 'Officer Polda',
            'nrp' => 'OF8888',
            'role' => 'officer',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 3. Create Operations
        $opPoldaId = Uuid::uuid7()->toString();
        DB::table('operations')->insert([
            'id' => $opPoldaId,
            'saker_id' => $this->sakerPolda->id,
            'name' => 'PH Polda Operation',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'created_by' => $this->officerPolda->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->operationPolda = Operation::withoutGlobalScopes()->find($opPoldaId);

        $opPolsekId = Uuid::uuid7()->toString();
        DB::table('operations')->insert([
            'id' => $opPolsekId,
            'saker_id' => $this->sakerPolsek->id,
            'name' => 'PH Polsek Operation',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'created_by' => $this->officerPolsek->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->operationPolsek = Operation::withoutGlobalScopes()->find($opPolsekId);

        // 4. Create Zones
        $zonePoldaId = Uuid::uuid7()->toString();
        DB::table('zones')->insert([
            'id' => $zonePoldaId,
            'operation_id' => $this->operationPolda->id,
            'saker_id' => $this->sakerPolda->id,
            'name' => 'Zone Polda',
            'is_active' => true,
            'created_by' => $this->officerPolda->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zonePolsekId = Uuid::uuid7()->toString();
        DB::table('zones')->insert([
            'id' => $zonePolsekId,
            'operation_id' => $this->operationPolsek->id,
            'saker_id' => $this->sakerPolsek->id,
            'name' => 'Zone Polsek',
            'is_active' => true,
            'created_by' => $this->officerPolsek->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Create Locations
        $locPoldaId = Uuid::uuid7()->toString();
        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, timezone
            ) VALUES (
                '{$locPoldaId}',
                '{$zonePoldaId}',
                '{$this->sakerPolda->id}',
                'Location Polda',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->officerPolda->id}',
                'Asia/Jakarta'
            )
        ");
        $this->locationPolda = Location::withoutGlobalScopes()->where('id', $locPoldaId)->firstOrFail();

        $locPolsekId = Uuid::uuid7()->toString();
        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, timezone
            ) VALUES (
                '{$locPolsekId}',
                '{$zonePolsekId}',
                '{$this->sakerPolsek->id}',
                'Location Polsek',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->officerPolsek->id}',
                'Asia/Jakarta'
            )
        ");
        $this->locationPolsek = Location::withoutGlobalScopes()->where('id', $locPolsekId)->firstOrFail();
    }

    public function test_cannot_assign_same_officer_to_overlapping_ph_within_same_saker(): void
    {
        $today = Carbon::today()->toDateString();
        auth()->login($this->sakerPolda);

        // 1. Assign first location
        $this->action->execute([
            'officer_id' => $this->officerPolda->id,
            'location_id' => $this->locationPolda->id,
            'operation_id' => $this->operationPolda->id,
            'saker_id' => $this->sakerPolda->id,
            'assigned_saker_id' => $this->sakerPolda->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerPolda);

        // 2. Try to assign to another location under the same Saker on the same date.
        $this->expectException(ValidationException::class);
        
        $this->action->execute([
            'officer_id' => $this->officerPolda->id,
            'location_id' => $this->locationPolda->id,
            'operation_id' => $this->operationPolda->id,
            'saker_id' => $this->sakerPolda->id,
            'assigned_saker_id' => $this->sakerPolda->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerPolda);
    }

    public function test_cannot_assign_same_officer_to_overlapping_ph_cross_tenant_saker(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Assign to Polda
        auth()->login($this->sakerPolda);
        $this->action->execute([
            'officer_id' => $this->officerPolsek->id,
            'location_id' => $this->locationPolda->id,
            'operation_id' => $this->operationPolda->id,
            'saker_id' => $this->sakerPolda->id,
            'assigned_saker_id' => $this->sakerPolda->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerPolda);

        // 2. Try to assign to Polsek (cross-tenant)
        auth()->login($this->sakerPolsek);
        $this->expectException(ValidationException::class);

        $this->action->execute([
            'officer_id' => $this->officerPolsek->id,
            'location_id' => $this->locationPolsek->id,
            'operation_id' => $this->operationPolsek->id,
            'saker_id' => $this->sakerPolsek->id,
            'assigned_saker_id' => $this->sakerPolsek->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerPolsek);
    }

    public function test_borrowing_down_hierarchy_is_allowed_and_correctly_saves_saker_id(): void
    {
        $today = Carbon::today()->toDateString();

        // Polda (parent) borrows Polsek (descendant) officer
        auth()->login($this->sakerPolda);

        $created = $this->action->execute([
            'officer_id' => $this->officerPolsek->id,
            'location_id' => $this->locationPolda->id,
            'operation_id' => $this->operationPolda->id,
            'saker_id' => $this->sakerPolda->id, // passed by controller as location saker
            'assigned_saker_id' => $this->sakerPolda->id, // borrowing saker
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerPolda);

        $this->assertCount(1, $created);
        $assignment = $created[0];

        // saker_id MUST be officer's home Saker (Polsek)
        $this->assertEquals($this->sakerPolsek->id, $assignment->saker_id);
        // assigned_saker_id MUST be borrowing Saker (Polda)
        $this->assertEquals($this->sakerPolda->id, $assignment->assigned_saker_id);
    }

    public function test_borrowing_up_hierarchy_is_blocked(): void
    {
        $today = Carbon::today()->toDateString();

        // Polsek (child) tries to borrow Polda (parent) officer
        auth()->login($this->sakerPolsek);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('hanya diperbolehkan meminjam dari Satker yang setingkat atau di bawahnya');

        $this->action->execute([
            'officer_id' => $this->officerPolda->id,
            'location_id' => $this->locationPolsek->id,
            'operation_id' => $this->operationPolsek->id,
            'saker_id' => $this->sakerPolsek->id,
            'assigned_saker_id' => $this->sakerPolsek->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerPolsek);
    }

    public function test_borrowing_sideways_sibling_hierarchy_is_blocked(): void
    {
        $today = Carbon::today()->toDateString();

        // Sibling Polsek B tries to borrow Polsek A officer
        auth()->login($this->sakerPolsekSibling);

        // We need to create a location and operation under Polsek Sibling first
        $opSiblingId = Uuid::uuid7()->toString();
        DB::table('operations')->insert([
            'id' => $opSiblingId,
            'saker_id' => $this->sakerPolsekSibling->id,
            'name' => 'PH Polsek Sibling Operation',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'created_by' => $this->officerPolsek->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $opSibling = Operation::withoutGlobalScopes()->find($opSiblingId);

        $zoneSiblingId = Uuid::uuid7()->toString();
        DB::table('zones')->insert([
            'id' => $zoneSiblingId,
            'operation_id' => $opSibling->id,
            'saker_id' => $this->sakerPolsekSibling->id,
            'name' => 'Zone Sibling',
            'is_active' => true,
            'created_by' => $this->officerPolsek->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $locSiblingId = Uuid::uuid7()->toString();
        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, timezone
            ) VALUES (
                '{$locSiblingId}',
                '{$zoneSiblingId}',
                '{$this->sakerPolsekSibling->id}',
                'Location Sibling',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->officerPolsek->id}',
                'Asia/Jakarta'
            )
        ");
        $locationSibling = Location::withoutGlobalScopes()->where('id', $locSiblingId)->firstOrFail();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('hanya diperbolehkan meminjam dari Satker yang setingkat atau di bawahnya');

        $this->action->execute([
            'officer_id' => $this->officerPolsek->id, // belongs to Polsek A
            'location_id' => $locationSibling->id,
            'operation_id' => $opSibling->id,
            'saker_id' => $this->sakerPolsekSibling->id,
            'assigned_saker_id' => $this->sakerPolsekSibling->id,
            'start_date' => $today,
            'end_date' => null,
        ], $this->sakerPolsekSibling);
    }
}
