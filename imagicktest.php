<?php
$filename = __DIR__ . '/tests/Imbo/Fixtures/640x160_rotated.jpg';

$imagick = new Imagick();
$imagick->readImageBlob(file_get_contents($filename));
echo "getImageOrientation() => " . $imagick->getImageOrientation() . PHP_EOL;
var_dump($imagick->getImageProperties());
var_dump(exif_read_data($filename));
