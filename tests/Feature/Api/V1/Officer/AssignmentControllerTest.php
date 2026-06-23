<?php

namespace Tests\Feature\Api\V1\Officer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class AssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Postgres-only');
        }
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/officer/assignments');

        $response->assertStatus(401);
    }

    public function test_out_of_range_date_returns_422(): void
    {
        [$user, $token] = $this->createOfficerWithToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/officer/assignments?date=2020-01-01');

        $response->assertStatus(422)
            ->assertJsonPath('reason_code', 'INVALID_DATE_RANGE');
    }

    private function createOfficerWithToken(): array
    {
        $sakerId = Uuid::uuid7()->toString();
        DB::table('sakers')->insert([
            'id' => $sakerId, 'name' => 'Test', 'code' => 'T-'.substr($sakerId, 0, 6),
            'type' => 'POLDA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $userId = Uuid::uuid7()->toString();
        DB::table('users')->insert([
            'id' => $userId, 'saker_id' => $sakerId, 'name' => 'Officer',
            'nrp' => 'NRP'.substr($userId, 0, 8), 'role' => 'officer',
            'password' => bcrypt('password'), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $user = User::withoutGlobalScopes()->find($userId);
        $token = $user->createToken('test')->plainTextToken;

        return [$user, $token];
    }
}
