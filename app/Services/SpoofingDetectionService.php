<?php

namespace App\Services;

use App\Models\User;

/**
 * SpoofingDetectionService — PRD §13.2.
 * Multi-signal GPS spoofing detection. Each signal = +1 to spoofing_score.
 * Score 1 → flagged for review. Score >= 2 → auto-rejected.
 *
 * Stub — full implementation in Phase 3 (check-in flow).
 */
class SpoofingDetectionService
{
    /**
     * Score a check-in payload for spoofing signals.
     *
     * @return object{score: int, signals: array}
     */
    public function score(object $payload, User $officer): object
    {
        $score = 0;
        $signals = [];

        // Signal: Mock location flag (auto-reject alone)
        if ($payload->mockLocation === true) {
            $score++;
            $signals[] = ['signal' => 'MOCK_LOCATION', 'value' => true];
        }

        // Signal: Suspicious GPS accuracy (< 3.0m is suspiciously precise)
        if ($payload->gpsAccuracy < 3.0) {
            $score++;
            $signals[] = ['signal' => 'SUSPICIOUS_ACCURACY', 'value' => $payload->gpsAccuracy];
        }

        // Signal: Timestamp drift (device vs server > 60 seconds)
        $deviceTime = strtotime($payload->timestampDevice);
        $serverTime = time();
        $drift = abs($serverTime - $deviceTime);
        if ($drift > 60) {
            $score++;
            $signals[] = ['signal' => 'TIMESTAMP_DRIFT', 'value' => $drift];
        }

        // Signal: Network-only provider with high accuracy
        if ($payload->gpsProvider === 'network' && $payload->gpsAccuracy < 5.0) {
            $score++;
            $signals[] = ['signal' => 'NETWORK_HIGH_ACCURACY', 'value' => $payload->gpsAccuracy];
        }

        // TODO (Phase 3): Speed plausibility check (distance / time > 200 km/h)
        // TODO (Phase 3): Repeated exact coordinates (compare to last 3 check-ins)

        return (object) [
            'score'   => $score,
            'signals' => $signals,
        ];
    }
}
