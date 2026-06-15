<?php

namespace Tests\Property;

use App\Models\Assignment;
use App\Models\Location;
use App\Models\Operation;
use App\Models\Saker;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * Base class for property-based tests.
 *
 * Provides Eris helpers plus minimal entity builders for DB-backed properties.
 * Does NOT use RefreshDatabase — DB-dependent subclasses opt in via
 * `requirePostgres()` in their own setUp() before any DB work.
 */
abstract class PropertyTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    /**
     * Skip the current test unless the default DB connection is Postgres.
     * Postgres-only tests call this at the start of their own setUp().
     */
    protected function requirePostgres(): void
    {
        try {
            $driver = DB::connection()->getDriverName();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Postgres-only property test — DB not reachable: '.$e->getMessage());
        }

        if ($driver !== 'pgsql') {
            $this->markTestSkipped('Postgres-only property test — current driver is '.$driver);
        }
    }

    protected function createSaker(array $overrides = []): Saker
    {
        return Saker::withoutGlobalScopes()->create(array_merge([
            'name' => 'Test Saker',
            'code' => 'TST-'.uniqid(),
            'type' => 'POLDA',
            'is_active' => true,
        ], $overrides));
    }

    protected function createOfficer(Saker $saker, array $overrides = []): User
    {
        return User::withoutGlobalScopes()->create(array_merge([
            'saker_id' => $saker->id,
            'name' => 'Officer Test',
            'nrp' => 'NRP'.uniqid(),
            'role' => 'officer',
            'password' => bcrypt('password'),
            'is_active' => true,
        ], $overrides));
    }

    protected function createAdmin(Saker $saker, array $overrides = []): User
    {
        return User::withoutGlobalScopes()->create(array_merge([
            'saker_id' => $saker->id,
            'name' => 'Admin Test',
            'nrp' => 'ADM'.uniqid(),
            'role' => 'saker_admin',
            'password' => bcrypt('password'),
            'is_active' => true,
        ], $overrides));
    }

    protected function createOperation(Saker $saker, User $createdBy, array $overrides = []): Operation
    {
        return Operation::withoutGlobalScopes()->create(array_merge([
            'saker_id' => $saker->id,
            'name' => 'Op Test',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '06:00',
            'end_time' => '22:00',
            'created_by' => $createdBy->id,
        ], $overrides));
    }

    protected function createZone(Saker $saker, Operation $operation, User $createdBy, array $overrides = []): Zone
    {
        return Zone::withoutGlobalScopes()->create(array_merge([
            'operation_id' => $operation->id,
            'saker_id' => $saker->id,
            'name' => 'Zone Test',
            'is_active' => true,
            'created_by' => $createdBy->id,
        ], $overrides));
    }

    protected function createLocation(Saker $saker, array $overrides = []): Location
    {
        $id = Uuid::uuid7()->toString();
        $data = array_merge([
            'saker_id' => $saker->id,
            'name' => 'Loc Test',
            'radius_meters' => 50,
            'minimum_officer' => 1,
            'is_active' => true,
        ], $overrides);

        DB::statement("
            INSERT INTO locations (id, zone_id, saker_id, name, description, address, radius_meters, minimum_officer, operating_hours, coords_locked, is_active, created_at, updated_at, created_by, coordinates, timezone)
            VALUES (?, ?, ?, ?, NULL, NULL, ?, ?, NULL, false, ?, NOW(), NOW(), ?, ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326), 'Asia/Jakarta')
        ", [
            $id,
            $data['zone_id'] ?? null,
            $data['saker_id'],
            $data['name'],
            $data['radius_meters'],
            $data['minimum_officer'],
            $data['is_active'],
            $data['created_by'] ?? null,
        ]);

        return Location::withoutGlobalScopes()->findOrFail($id);
    }

    protected function createAssignment(User $officer, Location $location, Operation $operation, Saker $saker, array $overrides = []): Assignment
    {
        return Assignment::withoutGlobalScopes()->create(array_merge([
            'officer_id' => $officer->id,
            'location_id' => $location->id,
            'operation_id' => $operation->id,
            'saker_id' => $saker->id,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ], $overrides));
    }

    /**
     * Create a full entity chain for Postgres-backed property tests.
     * Must only be called after requirePostgres() has passed.
     *
     * @return array{saker: Saker, officer: User, admin: User, operation: Operation, zone: Zone, location: Location, assignment: Assignment}
     */
    protected function createFullChain(array $overrides = []): array
    {
        $saker = $this->createSaker($overrides['saker'] ?? []);
        $officer = $this->createOfficer($saker, $overrides['officer'] ?? []);
        $admin = $this->createAdmin($saker, $overrides['admin'] ?? []);
        $operation = $this->createOperation($saker, $admin, $overrides['operation'] ?? []);
        $zone = $this->createZone($saker, $operation, $admin, $overrides['zone'] ?? []);
        $location = $this->createLocation($saker, array_merge(['zone_id' => $zone->id, 'created_by' => $admin->id], $overrides['location'] ?? []));
        $assignment = $this->createAssignment($officer, $location, $operation, $saker, $overrides['assignment'] ?? []);

        return compact('saker', 'officer', 'admin', 'operation', 'zone', 'location', 'assignment');
    }
}
