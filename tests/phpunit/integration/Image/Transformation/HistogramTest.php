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

use Imbo\Image\Transformation\Histogram,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\Histogram
 * @group integration
 * @group transformations
 */
class HistogramTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Histogram();
    }

    /**
     * Fetch different histogram parameters
     *
     * @return array[]
     */
    public function getHistogramParameters() {
        return [
            [1, 256],
            [2, 512],
            [4, 1024],
            [8, 2048],
        ];
    }

    /**
     * @dataProvider getHistogramParameters
     */
    public function testTransformWithDifferentParameters($scale, $resultingWidth = 256) {
        $blob = file_get_contents(FIXTURES_DIR . '/512x512.png');

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue($blob));
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(512));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(512));
        $image->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $image->expects($this->once())->method('setWidth')->with($resultingWidth)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->will($this->returnValue($image));
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
                  'scale' => $scale,
              ]));

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()->setImagick($imagick)->transform($event);
    }
}
