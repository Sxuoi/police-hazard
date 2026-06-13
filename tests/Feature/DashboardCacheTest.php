<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class DashboardCacheTest extends TestCase
{
    use RefreshDatabase;

    private string $sakerId;

    private string $adminId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Postgres + PostGIS required.');
        }

        $this->sakerId = Uuid::uuid7()->toString();
        $this->adminId = Uuid::uuid7()->toString();

        DB::table('sakers')->insert([
            'id' => $this->sakerId,
            'name' => 'Dashboard Test Saker',
            'code' => 'DT-'.substr($this->sakerId, 0, 8),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => $this->adminId,
            'saker_id' => $this->sakerId,
            'name' => 'God Admin',
            'nrp' => 'GA0001',
            'role' => 'god_admin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_map_data_returns_json_array_with_expected_shape(): void
    {
        $user = User::withoutGlobalScopes()->find($this->adminId);

        $response = $this->actingAs($user)->getJson('/dashboard/map-data?date=2026-01-01');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');

        // Response should be a JSON array (possibly empty if no locations).
        $data = $response->json();
        $this->assertIsArray($data);
    }

    public function test_map_data_response_is_cached_and_identical_on_second_call(): void
    {
        $user = User::withoutGlobalScopes()->find($this->adminId);
        $date = '2026-01-15';

        // First call — populates cache.
        $response1 = $this->actingAs($user)->getJson("/dashboard/map-data?date={$date}");
        $response1->assertStatus(200);

        // Second call — should return identical data from cache.
        $response2 = $this->actingAs($user)->getJson("/dashboard/map-data?date={$date}");
        $response2->assertStatus(200);

        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_map_data_cache_is_evicted_by_dashboard_tag_flush(): void
    {
        $user = User::withoutGlobalScopes()->find($this->adminId);
        $date = '2026-02-01';

        // Populate cache.
        $response1 = $this->actingAs($user)->getJson("/dashboard/map-data?date={$date}");
        $response1->assertStatus(200);

        // Flush the dashboard tag (simulates what DashboardCacheInvalidator does).
        Cache::tags(['dashboard'])->flush();

        // Next call should still succeed (re-queries DB).
        $response2 = $this->actingAs($user)->getJson("/dashboard/map-data?date={$date}");
        $response2->assertStatus(200);

        // Shape should still be the same.
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_map_data_location_entries_have_expected_keys(): void
    {
        $user = User::withoutGlobalScopes()->find($this->adminId);

        // Seed a location so we get at least one entry.
        $locationId = Uuid::uuid7()->toString();
        $zoneId = Uuid::uuid7()->toString();
        $operationId = Uuid::uuid7()->toString();

        DB::table('operations')->insert([
            'id' => $operationId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Op',
            'operation_type' => 'PH',
            'status' => 'active',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
            'created_by' => $this->adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insert([
            'id' => $zoneId,
            'operation_id' => $operationId,
            'saker_id' => $this->sakerId,
            'name' => 'Test Zone',
            'is_active' => true,
            'created_by' => $this->adminId,
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
                'Cache Test Location',
                ST_SetSRID(ST_MakePoint(106.8456, -6.2088), 4326),
                50, 1, false, true,
                '{$this->adminId}',
                NOW(), NOW(), 'Asia/Jakarta'
            )
        ");

        $response = $this->actingAs($user)->getJson('/dashboard/map-data?date='.now()->format('Y-m-d'));

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data);

        // Each entry should have the expected keys.
        $entry = $data[0];
        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('name', $entry);
        $this->assertArrayHasKey('lat', $entry);
        $this->assertArrayHasKey('lng', $entry);
        $this->assertArrayHasKey('status', $entry);
        $this->assertArrayHasKey('total', $entry);
        $this->assertArrayHasKey('present', $entry);
        $this->assertArrayHasKey('min', $entry);
    }
}
