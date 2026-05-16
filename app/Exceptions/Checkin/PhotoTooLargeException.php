<?php

namespace App\Exceptions\Checkin;

/**
 * Thrown when the uploaded photo exceeds the configured max size.
 * HTTP 422, NOT bypass-eligible.
 */
final class PhotoTooLargeException extends CheckinException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'PHOTO_TOO_LARGE',
            httpStatus: 422,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
