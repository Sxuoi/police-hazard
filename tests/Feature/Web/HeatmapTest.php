<?php

namespace Tests\Feature\Web;

use App\Models\Saker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class HeatmapTest extends TestCase
{
    use RefreshDatabase;

    private Saker $godAdminSaker;
    private Saker $sakerAdminSaker;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires Postgres + PostGIS.');
        }

        // Create MABES saker (God Admin context)
        $this->godAdminSaker = Saker::create([
            'id' => Uuid::uuid7()->toString(),
            'name' => 'MABES POLRI',
            'code' => 'MABES-POLRI',
            'type' => 'MABES',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Create POLDA saker (Saker Admin context)
        $this->sakerAdminSaker = Saker::create([
            'id' => Uuid::uuid7()->toString(),
            'name' => 'POLDA JAWA TENGAH',
            'code' => 'POLDA-JATENG',
            'type' => 'POLDA',
            'email' => 'poldajateng@gmail.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_heatmap_routes(): void
    {
        $response = $this->get(route('heatmap'));
        $response->assertRedirect(route('login'));

        $responseApi = $this->get(route('admin.heatmap.data'));
        $responseApi->assertStatus(401);
    }

    public function test_god_admin_can_access_heatmap_index(): void
    {
        $response = $this->actingAs($this->godAdminSaker, 'web')
            ->get(route('heatmap'));

        $response->assertStatus(200);
        $response->assertViewIs('heatmap.index');
    }

    public function test_saker_admin_cannot_access_heatmap_index(): void
    {
        $response = $this->actingAs($this->sakerAdminSaker, 'web')
            ->get(route('heatmap'));

        $response->assertStatus(403);
    }

    public function test_god_admin_can_fetch_heatmap_data(): void
    {
        $startDate = now()->subDays(10)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->godAdminSaker, 'web')
            ->get(route('admin.heatmap.data', [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'coverage' => ['type', 'features'],
            'absences',
            'spoofing',
            'density',
        ]);
    }

    public function test_heatmap_data_validation_range_limit(): void
    {
        $startDate = now()->subDays(100)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->godAdminSaker, 'web')
            ->get(route('admin.heatmap.data', [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }
}
