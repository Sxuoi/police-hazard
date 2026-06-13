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
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $image = $manager->decodePath($rawPhotoPath);

        // Kompres dimensi foto jika lebih besar dari 1200px lebar
        // Ini memastikan ukuran file lebih kecil dan watermark selalu proporsional
        $image->scaleDown(width: 1200);

        // Place Logo at top-left
        $logoPath = public_path('images/logo_libas.png');
        if (file_exists($logoPath)) {
            $logo = $manager->decodePath($logoPath);
            $logo->scale(width: 100);
            
            $logoShadow = clone $logo;
            $logoShadow->brightness(-100);
            $image->insert($logoShadow, 22, 22, 'top-left');
            
            $image->insert($logo, 20, 20, 'top-left');
        }

        // Font selection
        $fontPath = null;
        if (file_exists('C:\Windows\Fonts\arial.ttf')) {
            $fontPath = 'C:\Windows\Fonts\arial.ttf';
        }

        // Add Waktu at top-right
        $waktu = $watermarkData['Waktu'] ?? now()->format('d-m-Y H:i:s');
        
        // Shadow Waktu
        $image->text($waktu, $image->width() - 18, 52, function($font) use ($fontPath) {
            if ($fontPath) $font->filename($fontPath);
            $font->size(32);
            $font->color('rgba(0,0,0,0.8)');
            $font->align('right');
        });
        
        // Main Waktu
        $image->text($waktu, $image->width() - 20, 50, function($font) use ($fontPath) {
            if ($fontPath) $font->filename($fontPath);
            $font->size(32);
            $font->color('#ffffff');
            $font->align('right');
        });

        // Add Alamat and Koordinat at bottom
        $alamat = "Alamat 110: " . ($watermarkData['Alamat'] ?? 'Tidak diketahui');
        $koordinat = "Koordinat 110: " . ($watermarkData['Koordinat'] ?? '-');

        // Shadow Alamat
        $image->text($alamat, 22, $image->height() - 78, function($font) use ($fontPath) {
            if ($fontPath) $font->filename($fontPath);
            $font->size(28);
            $font->color('rgba(0,0,0,0.8)');
            $font->align('left');
        });

        // Main Alamat
        $image->text($alamat, 20, $image->height() - 80, function($font) use ($fontPath) {
            if ($fontPath) $font->filename($fontPath);
            $font->size(28);
            $font->color('#ffffff');
            $font->align('left');
        });

        // Shadow Koordinat
        $image->text($koordinat, 22, $image->height() - 38, function($font) use ($fontPath) {
            if ($fontPath) $font->filename($fontPath);
            $font->size(28);
            $font->color('rgba(0,0,0,0.8)');
            $font->align('left');
        });

        // Main Koordinat
        $image->text($koordinat, 20, $image->height() - 40, function($font) use ($fontPath) {
            if ($fontPath) $font->filename($fontPath);
            $font->size(28);
            $font->color('#ffffff');
            $font->align('left');
        });

        // Save the image with 75% quality for compression
        $image->save($rawPhotoPath, quality: 75);
        return $rawPhotoPath;
    }
}
