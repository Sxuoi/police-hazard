<?php

namespace App\Exceptions\Checkin;

use RuntimeException;

/**
 * Abstract base for all check-in pipeline exceptions.
 * Design §3.4 — carries reason_code, httpStatus, bypassEligible, and extra data.
 */
abstract class CheckinException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
        public readonly int $httpStatus,
        public readonly bool $bypassEligible,
        public readonly array $extra = [],
    ) {
        parent::__construct($reasonCode);
    }
}
