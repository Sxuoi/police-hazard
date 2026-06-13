<?php

namespace Tests\Feature\Api\V1\Officer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceHistoryControllerTest extends TestCase
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
        $response = $this->getJson('/api/v1/officer/attendance/history');

        $response->assertStatus(401);
    }
}
