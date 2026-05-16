<?php

namespace App\Exceptions\Bypass;

use RuntimeException;

/**
 * Abstract base for all bypass workflow exceptions.
 * Same structure as CheckinException: reason_code, httpStatus, bypassEligible, extra.
 */
abstract class BypassException extends RuntimeException
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
