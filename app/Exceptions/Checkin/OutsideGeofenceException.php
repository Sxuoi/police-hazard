<?php

namespace App\Exceptions\Checkin;

/**
 * Thrown when the officer's GPS coordinates are outside the location geofence.
 * HTTP 422, bypass-eligible. Extra carries distance_meters.
 */
final class OutsideGeofenceException extends CheckinException
{
    public function __construct(float $distanceMeters, array $extra = [])
    {
        parent::__construct(
            reasonCode: 'OUTSIDE_GEOFENCE',
            httpStatus: 422,
            bypassEligible: true,
            extra: array_merge(['distance_meters' => $distanceMeters], $extra),
        );
    }
}
