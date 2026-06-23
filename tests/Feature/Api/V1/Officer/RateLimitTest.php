<?php

namespace Tests\Feature\Api\V1\Officer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private string $sakerId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Postgres-only');
        }

        // Clear any existing rate limiter state.
        RateLimiter::clear('officer-login');

        $this->sakerId = Uuid::uuid7()->toString();
        DB::table('sakers')->insert([
            'id' => $this->sakerId,
            'name' => 'Rate Limit Saker',
            'code' => 'RL-'.substr($this->sakerId, 0, 8),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create an officer user for login attempts.
        DB::table('users')->insert([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerId,
            'name' => 'Rate Limit Officer',
            'nrp' => 'RL0001',
            'role' => 'officer',
            'password' => Hash::make('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_login_rate_limit_returns_429_after_burst(): void
    {
        $maxAttempts = (int) config('policehazard.auth.max_login_attempts', 5);

        // Send max_login_attempts requests with wrong password — all should get 401.
        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'nrp' => 'RL0001',
                'password' => 'wrong-password',
            ]);

            $this->assertContains(
                $response->getStatusCode(),
                [401, 422],
                "Request #{$i} should not be rate-limited yet.",
            );
        }

        // The next request should be rate-limited (429).
        $response = $this->postJson('/api/v1/auth/login', [
            'nrp' => 'RL0001',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJson([
                'reason_code' => 'RATE_LIMITED',
                'status' => 429,
            ])
            ->assertJsonStructure(['retry_after_seconds']);
    }
}
