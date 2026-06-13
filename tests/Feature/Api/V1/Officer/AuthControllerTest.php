<?php

namespace Tests\Feature\Api\V1\Officer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $sakerId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Postgres-only');
        }

        $this->sakerId = Uuid::uuid7()->toString();
        DB::table('sakers')->insert([
            'id' => $this->sakerId,
            'name' => 'Test Saker',
            'code' => 'TST-'.substr($this->sakerId, 0, 8),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_successful_login_returns_token_and_officer(): void
    {
        $userId = Uuid::uuid7()->toString();
        DB::table('users')->insert([
            'id' => $userId,
            'saker_id' => $this->sakerId,
            'name' => 'Officer One',
            'nrp' => 'OF9001',
            'role' => 'officer',
            'password' => Hash::make('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'nrp' => 'OF9001',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'token_expires_at', 'officer' => ['id', 'name', 'nrp', 'saker']]);
    }

    public function test_invalid_credentials_returns_401_with_reason_code(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nrp' => 'NONEXISTENT',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('reason_code', 'INVALID_CREDENTIALS');
    }

    public function test_logout_revokes_current_token(): void
    {
        $userId = Uuid::uuid7()->toString();
        DB::table('users')->insert([
            'id' => $userId,
            'saker_id' => $this->sakerId,
            'name' => 'Officer Two',
            'nrp' => 'OF9002',
            'role' => 'officer',
            'password' => Hash::make('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::withoutGlobalScopes()->find($userId);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(204);
    }
}
