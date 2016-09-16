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

use Imbo\Image\Transformation\Canvas,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\Canvas
 * @group integration
 * @group transformations
 */
class CanvasTest extends TransformationTests {
    /**
     * @var int
     */
    private $width = 700;

    /**
     * @var int
     */
    private $height = 500;

    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Canvas();
    }

    /**
     * Fetch different canvas parameters
     *
     * @return array[]
     */
    public function getCanvasParameters() {
        return [
            // free mode with only width
            [1000, null, 'free', 1000, 463],

            // free mode with only height
            [null, 1000, 'free', 665, 1000],

            // free mode where both sides are smaller than the original
            [200, 200, 'free', 200, 200],

            // free mode where height is smaller than the original
            [1000, 200, 'free', 1000, 200],

            // free mode where width is smaller than the original
            [200, 1000, 'free', 200, 1000],

            // center, center-x and center-y modes
            [1000, 1000, 'center', 1000, 1000],
            [1000, 1000, 'center-x', 1000, 1000],
            [1000, 1000, 'center-y', 1000, 1000],

            // center, center-x and center-y modes where one of the sides are smaller than the
            // original
            [1000, 200, 'center', 1000, 200],
            [200, 1000, 'center', 200, 1000],
            [1000, 200, 'center-x', 1000, 200],
            [1000, 200, 'center-y', 1000, 200],

            // center, center-x and center-y modes where both sides are smaller than the original
            [200, 200, 'center', 200, 200],
            [200, 200, 'center-x', 200, 200],
            [200, 200, 'center-y', 200, 200],
        ];
    }

    /**
     * @dataProvider getCanvasParameters
     */
    public function testTransformWithDifferentParameters($width, $height, $mode = 'free', $resultingWidth = 665, $resultingHeight = 463) {
        $blob = file_get_contents(FIXTURES_DIR . '/image.png');

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue($blob));
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $image->expects($this->once())->method('setWidth')->with($resultingWidth)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($resultingHeight)->will($this->returnValue($image));
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))
              ->method('getArgument')
              ->with('image')
              ->will($this->returnValue($image));
        $event->expects($this->at(1))
              ->method('getArgument')
              ->with('params')
              ->will($this->returnValue([
                  'width' => $width,
                  'height' => $height,
                  'mode' => $mode,
              ]));

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()->setImagick($imagick)->transform($event);
    }
}
