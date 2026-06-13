<?php
require 'vendor/autoload.php';
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

$manager = new ImageManager(new Driver());

$logoPath = 'public/images/logo_libas.png';
$image = clone $manager->decodePath($logoPath); // just use logo as base to test

if (file_exists($logoPath)) {
    $logo = $manager->decodePath($logoPath);
    $logo->scale(width: 50);
    
    // Logo shadow
    $logoShadow = clone $logo;
    $logoShadow->brightness(-100); // make it black
    $image->insert($logoShadow, 12, 12, 'top-left');
    $image->insert($logo, 10, 10, 'top-left');
}

$fontPath = 'C:\Windows\Fonts\arial.ttf';

// Shadow
$image->text('Test', 22, 52, function($font) use ($fontPath) {
    if ($fontPath) $font->filename($fontPath);
    $font->size(20);
    $font->color('rgba(0,0,0,0.8)');
    $font->align('left');
});
// Main Text
$image->text('Test', 20, 50, function($font) use ($fontPath) {
    if ($fontPath) $font->filename($fontPath);
    $font->size(20);
    $font->color('#ffffff');
    $font->align('left');
});

$image->save('scratch_output.png');
echo "Done";
