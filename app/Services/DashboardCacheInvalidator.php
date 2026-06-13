<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Coarse cache invalidation for the dashboard map-data layer.
 *
 * Design §10 — flushes all Redis-tagged 'dashboard' entries whenever
 * a new attendance is recorded. Tolerates Redis and Log failures
 * gracefully (R9.5: silently swallow if both fail).
 */
final class DashboardCacheInvalidator
{
    public function invalidateFor(Attendance $att): void
    {
        try {
            Cache::store('redis')->tags(['dashboard'])->flush();
        } catch (Throwable $e) {
            try {
                Log::warning('cache_invalidation_failed', [
                    'error' => $e->getMessage(),
                    'attendance_id' => $att->id,
                ]);
            } catch (Throwable) {
                /* R9.5: silently swallow if both Redis and Log fail */
            }
        }
    }
}
