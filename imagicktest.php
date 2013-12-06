<?php
$imagick = new Imagick();
$imagick->readImageBlob(file_get_contents('tests/Imbo/Fixtures/640x160_rotated.jpg'));

echo "getImageOrientation() => " . $imagick->getImageOrientation() . PHP_EOL;
echo "getImageProperties()['exif:Orientation'] => " . $imagick->getImageProperties()['exif:Orientation'] . PHP_EOL;
