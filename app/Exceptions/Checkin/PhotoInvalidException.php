<?php

namespace App\Exceptions\Checkin;

/**
 * Thrown when the uploaded photo fails magic-bytes or MIME validation.
 * HTTP 422, NOT bypass-eligible.
 */
final class PhotoInvalidException extends CheckinException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'PHOTO_INVALID',
            httpStatus: 422,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
