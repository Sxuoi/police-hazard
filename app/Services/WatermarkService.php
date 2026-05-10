<?php

namespace App\Services;

/**
 * WatermarkService — PRD §9.6.
 * Applies watermark overlay to check-in photos using Intervention Image v3.
 *
 * Stub — full implementation in Phase 3 (photo processing pipeline).
 */
class WatermarkService
{
    /**
     * Apply watermark to a check-in photo.
     *
     * @param string $rawPhotoPath   Path to the raw uploaded photo
     * @param array  $watermarkData  Data for watermark overlay (officer name, NRP, location, coords, time, distance)
     * @param string $sakerLogoPath  Path to the Saker logo for top-right overlay
     * @return string Path to the watermarked photo
     */
    public function applyWatermark(string $rawPhotoPath, array $watermarkData, ?string $sakerLogoPath = null): string
    {
        // TODO: Implement using Intervention Image v3
        // Elements per PRD §9.6:
        // - Saker Logo: top-right, 80x80px, 80% opacity
        // - Officer Name + NRP: bottom banner line 1, bold white
        // - Location Name: bottom banner line 2
        // - GPS Coordinates: bottom banner line 3, 6 decimal places
        // - Date + Time: bottom banner line 4, DD-MM-YYYY HH:MM:SS WIB
        // - Distance: bottom banner line 5, "Jarak: X.Xm dari titik"

        return $rawPhotoPath; // Passthrough until implemented
    }
}
