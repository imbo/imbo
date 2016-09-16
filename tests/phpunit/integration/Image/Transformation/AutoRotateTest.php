<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Model\Image,
    Imbo\Image\Transformation\AutoRotate,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\AutoRotate
 * @group integration
 * @group transformations
 */
class AutoRotateTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new AutoRotate();
    }

    /**
     * Return different files to test with
     *
     * @return array[]
     */
    public function getFiles() {
        return [
            'orientation1.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation1.jpeg', false, false],
            'orientation2.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation2.jpeg', false, true],
            'orientation3.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation3.jpeg', false, true],
            'orientation4.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation4.jpeg', false, true],
            'orientation5.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation5.jpeg', true, true],
            'orientation6.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation6.jpeg', true, true],
            'orientation7.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation7.jpeg', true, true],
            'orientation8.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation8.jpeg', true, true],
        ];
    }

    /**
     * @dataProvider getFiles
     */
    public function testAutoRotatesAllOrientations($file, $changeDimensions, $transformed) {
        $colorValues = [
            [
                'x' => 0,
                'y' => 0,
                'color' => 'rgb(128,63,193)'
            ],
            [
                'x' => 0,
                'y' => 1000,
                'color' => 'rgb(254,57,126)'
            ],
            [
                'x' => 1000,
                'y' => 0,
                'color' => 'rgb(127,131,194)'
            ],
            [
                'x' => 1000,
                'y' => 1000,
                'color' => 'rgb(249,124,192)'
            ],
        ];

        /**
         * Load the image, perform the auto rotate tranformation and check that the color codes in
         * the four corner pixels match the known color values as defined in $colorValues
         */
        $blob = file_get_contents($file);

        $image = $this->getMock('Imbo\Model\Image');

        if ($changeDimensions) {
            $image->expects($this->once())->method('setWidth')->with(350)->will($this->returnValue($image));
            $image->expects($this->once())->method('setHeight')->with(350)->will($this->returnValue($image));
        } else {
            $image->expects($this->never())->method('setWidth');
            $image->expects($this->never())->method('setHeight');
        }

        if ($transformed) {
            $image->expects($this->once())->method('hasBeenTransformed')->with(true);
        } else {
            $image->expects($this->never())->method('hasBeenTransformed');
        }

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getArgument')->with('image')->will($this->returnValue($image));

        // Perform the auto rotate transformation on the image
        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()->setImagick($imagick)
                                  ->transform($event);

        // Do assertion comparison on the color values
        foreach ($colorValues as $pixelInfo) {
            $pixelValue = $imagick->getImagePixelColor($pixelInfo['x'], $pixelInfo['y'])
                                  ->getColorAsString();

            $this->assertStringEndsWith($pixelInfo['color'], $pixelValue);
        }
    }
}
