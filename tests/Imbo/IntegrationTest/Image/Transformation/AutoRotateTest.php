<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\InitegrationTest\Image\Transformation;

use Imbo\Model\Image,
    Imbo\Image\Transformation\AutoRotate,
    Imagick;

/**
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Test suite\Integration tests
 */
class AutoRotateTest extends \PHPUnit_Framework_TestCase {
    /**
     * Return different files to test with
     *
     * @return array[]
     */
    public function getFiles() {
        $files = array();

        for ($i = 1; $i <= 8; $i++) {
            $filename = 'orientation' . $i . '.jpeg';
            $files[$filename] = array(FIXTURES_DIR . '/autoRotate/' . $filename);
        }

        return $files;
    }

    /**
     * @dataProvider getFiles
     * @covers Imbo\Image\Transformation\AutoRotate::applyToImage
     */
    public function testAutoRotatesAllOrientations($file) {
        $colorValues = array(
            array(
                'x' => 0,
                'y' => 0,
                'color' => 'rgb(128,63,193)'
            ),
            array(
                'x' => 0,
                'y' => 1000,
                'color' => 'rgb(254,57,126)'
            ),
            array(
                'x' => 1000,
                'y' => 0,
                'color' => 'rgb(127,131,194)'
            ),
            array(
                'x' => 1000,
                'y' => 1000,
                'color' => 'rgb(249,124,192)'
            ),
        );

        /**
         * Load the image, perform the auto rotate tranformation and check that the color codes in
         * the four corner pixels match the known color values as defined in $colorValues
         */
        $image = new Image();
        $image->setBlob(file_get_contents($file));

        // Perform the auto rotate transformation on the image
        $transformation = new AutoRotate();
        $transformation->applyToImage($image);

        // Do assertion comparison on the color values
        $imagick = new Imagick();
        $imagick->readImageBlob($image->getBlob());

        foreach ($colorValues as $pixelInfo) {
            $pixelValue = $imagick->getImagePixelColor($pixelInfo['x'], $pixelInfo['y'])
                                  ->getColorAsString();

            $this->assertStringEndsWith($pixelInfo['color'], $pixelValue);
        }
    }
}
