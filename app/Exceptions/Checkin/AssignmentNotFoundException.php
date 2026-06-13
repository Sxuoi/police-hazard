<?php

namespace App\Exceptions\Checkin;

/**
 * Thrown when the assignment does not exist or does not belong to the officer.
 * HTTP 422, NOT bypass-eligible.
 */
final class AssignmentNotFoundException extends CheckinException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'ASSIGNMENT_NOT_FOUND',
            httpStatus: 422,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
