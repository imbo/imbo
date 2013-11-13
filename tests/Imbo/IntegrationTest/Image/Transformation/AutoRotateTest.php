<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Image\Transformation;

use Imbo\Model\Image,
    Imbo\Image\Transformation\AutoRotate,
    Imagick;

/**
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Test suite\Integration tests
 * @covers Imbo\Image\Transformation\AutoRotate
 */
class AutoRotateTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new AutoRotate();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParams() {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/autoRotate/orientation2.jpeg')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->any())->method('setWidth')->with(671)->will($this->returnValue($image));
        $image->expects($this->any())->method('setHeight')->with(471)->will($this->returnValue($image));

        return $image;
    }

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

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getArgument')->with('image')->will($this->returnValue($image));

        // Perform the auto rotate transformation on the image
        $this->getTransformation()->transform($event);

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
