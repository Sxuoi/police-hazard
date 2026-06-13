<?php
require 'vendor/autoload.php';
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

$manager = new ImageManager(new Driver());

$imagePath = 'public/images/logo_libas.png';
$image = $manager->decodePath($imagePath);

try {
    $image->text('Hello World', 20, 50, function($font) {
        if (file_exists('C:\Windows\Fonts\arial.ttf')) {
            $font->filename('C:\Windows\Fonts\arial.ttf');
        }
        $font->size(24);
        $font->color('#ffffff');
        $font->align('left');
        $font->valign('bottom');
    });
    $image->save('test_watermark.jpg');
    echo "Success!";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
