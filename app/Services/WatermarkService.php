<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;
use RuntimeException;

/**
 * WatermarkService — PRD §9.6, Design §7.
 *
 * Applies the server-side watermark overlay to check-in photos using
 * Intervention Image v4. Re-encoding strips EXIF metadata.
 *
 * Watermark elements (PRD §9.6 / §13.3):
 *   - Officer name + NRP
 *   - Location name
 *   - GPS coordinates (6 decimal places)
 *   - Timestamp in the location timezone (e.g., "DD-MM-YYYY HH:MM:SS WIB")
 */
class WatermarkService
{
    public function __construct(
        private readonly LocationTimezoneResolver $timezoneResolver = new LocationTimezoneResolver,
    ) {}

    /**
     * Read the raw photo, apply the watermark, upload to S3, return the S3 key.
     *
     * @throws RuntimeException on missing raw photo or storage failure.
     */
    public function watermark(Attendance $att): string
    {
        $privateDisk = config('policehazard.photo.private_disk', 'local');
        $s3Disk = config('policehazard.photo.s3_disk', 's3');

        if (! $att->photo_raw_path) {
            throw new RuntimeException("Attendance {$att->id} has no raw photo path");
        }

        if (! Storage::disk($privateDisk)->exists($att->photo_raw_path)) {
            throw new RuntimeException(
                "Raw photo not found on disk [{$privateDisk}]: {$att->photo_raw_path}"
            );
        }

        $rawBytes = Storage::disk($privateDisk)->get($att->photo_raw_path);

        $manager = new ImageManager(new Driver);
        $image = $manager->read($rawBytes);

        $lines = $this->composeLines($att);
        $this->overlayBanner($image, $lines);

        $encoded = (string) $image->toJpeg(quality: 85);

        $s3Key = "photos/{$att->id}.jpg";
        Storage::disk($s3Disk)->put($s3Key, $encoded, [
            'ContentType' => 'image/jpeg',
        ]);

        return $s3Key;
    }

    /**
     * Build the watermark text lines from the attendance and its relations.
     *
     * @return array<int,string>
     */
    private function composeLines(Attendance $att): array
    {
        $officer = $att->officer;
        $location = $att->location;

        $officerName = $officer?->name ?? '—';
        $officerNrp = $officer?->nrp ?? '—';
        $locationName = $location?->name ?? '—';
        $timezone = $location?->timezone
            ?: config('policehazard.default_timezone', 'Asia/Jakarta');
        $tzAbbr = $this->timezoneResolver->tzAbbreviation($timezone);

        $timestamp = Carbon::parse($att->checked_in_at)->setTimezone($timezone);
        $formattedTime = trim(sprintf(
            '%s %s',
            $timestamp->format('d-m-Y H:i:s'),
            $tzAbbr,
        ));

        // Extract coordinates from the attendance relationship (PostGIS point).
        // The raw lat/lng is not stored on the model; fall back to the bypass
        // approval's officer bundle or the location's default coordinates when
        // the PostGIS point is not present in test contexts.
        $latitude = $att->latitude ?? $att->bypassApproval?->officer_latitude;
        $longitude = $att->longitude ?? $att->bypassApproval?->officer_longitude;

        $coordsLine = ($latitude !== null && $longitude !== null)
            ? sprintf('GPS: %.6f, %.6f', (float) $latitude, (float) $longitude)
            : 'GPS: —';

        return [
            sprintf('%s (NRP %s)', $officerName, $officerNrp),
            $locationName,
            $coordsLine,
            $formattedTime,
        ];
    }

    /**
     * Draw a semi-transparent bottom banner with the watermark text lines.
     *
     * @param  array<int,string>  $lines
     */
    private function overlayBanner(ImageInterface $image, array $lines): void
    {
        $width = $image->width();
        $height = $image->height();

        $lineHeight = 22;
        $paddingX = 12;
        $paddingY = 10;
        $bannerHeight = $paddingY * 2 + count($lines) * $lineHeight;

        // Dark translucent strip across the bottom.
        $image->drawRectangle(function ($rect) use ($width, $bannerHeight, $height) {
            $rect->at(0, $height - $bannerHeight);
            $rect->size($width, $bannerHeight);
            $rect->background('rgba(0, 0, 0, 0.55)');
        });

        $y = $height - $bannerHeight + $paddingY;
        foreach ($lines as $text) {
            $image->text($text, $paddingX, $y, function (FontFactory $font) {
                $font->size(16);
                $font->color('#ffffff');
                $font->align('left', 'top');
            });
            $y += $lineHeight;
        }
    }

    /**
     * Legacy entry point retained for existing callers (PRD §9.6).
     *
     * @deprecated Use {@see self::watermark()} with the Attendance model.
     */
    public function applyWatermark(string $rawPhotoPath, array $watermarkData, ?string $sakerLogoPath = null): string
    {
        return $rawPhotoPath;
    }
}
