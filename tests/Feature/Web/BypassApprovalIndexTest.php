<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class BypassApprovalIndexTest extends TestCase
{
    use RefreshDatabase;

    private string $sakerId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires Postgres + PostGIS.');
        }

        $this->sakerId = Uuid::uuid7()->toString();

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
    }

    public function test_authenticated_admin_can_access_bypass_approvals_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('bypass-approvals.index'));

        $response->assertStatus(200);
        $response->assertViewIs('bypass-approvals.index');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('bypass-approvals.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_defaults_to_pending_filter(): void
    {
        $response = $this->actingAs($this->admin)->get(route('bypass-approvals.index'));

        $response->assertStatus(200);
        $response->assertViewHas('filters', function ($filters) {
            return $filters['status'] === 'pending';
        });
    }
}
