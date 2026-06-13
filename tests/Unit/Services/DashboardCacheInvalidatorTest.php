<?php

namespace Tests\Unit\Services;

use App\Models\Attendance;
use App\Services\DashboardCacheInvalidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class DashboardCacheInvalidatorTest extends TestCase
{
    #[Test]
    public function test_flushes_dashboard_cache_tags(): void
    {
        $taggedCache = Mockery::mock();
        $taggedCache->shouldReceive('flush')->once();

        $store = Mockery::mock();
        $store->shouldReceive('tags')->with(['dashboard'])->once()->andReturn($taggedCache);

        Cache::shouldReceive('store')->with('redis')->once()->andReturn($store);

        $attendance = new Attendance;
        $attendance->id = 'test-attendance-id';

        $invalidator = new DashboardCacheInvalidator;
        $invalidator->invalidateFor($attendance);
    }

    #[Test]
    public function test_handles_redis_down_gracefully(): void
    {
        $store = Mockery::mock();
        $store->shouldReceive('tags')
            ->with(['dashboard'])
            ->once()
            ->andThrow(new RuntimeException('Redis connection refused'));

        Cache::shouldReceive('store')->with('redis')->once()->andReturn($store);
        Log::shouldReceive('warning')->once()->with('cache_invalidation_failed', Mockery::on(function ($context) {
            return $context['error'] === 'Redis connection refused'
                && $context['attendance_id'] === 'test-attendance-id';
        }));

        $attendance = new Attendance;
        $attendance->id = 'test-attendance-id';

        $invalidator = new DashboardCacheInvalidator;

        // Should not throw
        $invalidator->invalidateFor($attendance);
    }

    #[Test]
    public function test_handles_log_down_gracefully(): void
    {
        $store = Mockery::mock();
        $store->shouldReceive('tags')
            ->with(['dashboard'])
            ->once()
            ->andThrow(new RuntimeException('Redis connection refused'));

        Cache::shouldReceive('store')->with('redis')->once()->andReturn($store);
        Log::shouldReceive('warning')->once()->andThrow(new RuntimeException('Log transport failed'));

        $attendance = new Attendance;
        $attendance->id = 'test-attendance-id';

        $invalidator = new DashboardCacheInvalidator;

        // Should not throw even when both Cache and Log fail (R9.5)
        $invalidator->invalidateFor($attendance);
    }
}
