<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\DB;

/**
 * GeofenceService — PRD §20.3.
 * Handles PostGIS geospatial validation for check-in geofencing.
 * Uses ST_DWithin via ::geography cast for metre-accurate comparison (PRD §16.3).
 */
class GeofenceService
{
    /**
     * Returns the distance in metres between submitted GPS and location point.
     */
    public function distanceFromLocation(Location $location, float $lat, float $lng): float
    {
        $result = DB::selectOne('
            SELECT ST_Distance(
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                coordinates::geography
            ) AS distance_metres
            FROM locations WHERE id = ?
        ', [$lng, $lat, $location->id]);

        return round($result->distance_metres, 2);
    }

    /**
     * Checks if submitted GPS is within the location's geofence radius.
     * Uses ST_DWithin which is inclusive at the boundary (PRD T-01).
     */
    public function isWithinGeofence(Location $location, float $lat, float $lng): bool
    {
        $result = DB::selectOne('
            SELECT ST_DWithin(
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                coordinates::geography,
                ?
            ) AS within
            FROM locations WHERE id = ?
        ', [$lng, $lat, $location->radius_meters, $location->id]);

        return (bool) $result->within;
    }
}
