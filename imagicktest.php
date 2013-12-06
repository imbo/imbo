<?php
$imagick = new Imagick();
$imagick->readImageBlob(file_get_contents('tests/Imbo/Fixtures/640x160_rotated.jpg'));

echo "getImageOrientation() => " . $imagick->getImageOrientation() . PHP_EOL;
var_dump($imagick->getImageProperties());
