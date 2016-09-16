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

use Imbo\Image\Transformation\Resize,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\Resize
 * @group integration
 * @group transformations
 */
class ResizeTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Resize();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getResizeParams() {
        return [
            'only width' => [
                'params'         => ['width' => 100],
                'transformation' => true,
                'resizedWidth'   => 100,
                'resizedHeight'  => 70,
            ],
            'only height' => [
                'params'         => ['height' => 100],
                'transformation' => true,
                'resizedWidth'   => 144,
                'resizedHeight'  => 100,
            ],
            'width and height' => [
                'params'         => ['width' => 100, 'height' => 200],
                'transformation' => true,
                'resizedWidth'   => 100,
                'resizedHeight'  => 200,
            ],
            'params match image size' => [
                'params'         => ['width' => 665, 'height' => 463],
                'transformation' => false
            ],
        ];
    }

    /**
     * @dataProvider getResizeParams
     * @covers Imbo\Image\Transformation\Resize::transform
     */
    public function testCanTransformImage($params, $transformation, $resizedWidth = null, $resizedHeight = null) {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));

        if ($transformation) {
            $image->expects($this->once())->method('setWidth')->with($resizedWidth)->will($this->returnValue($image));
            $image->expects($this->once())->method('setHeight')->with($resizedHeight)->will($this->returnValue($image));
            $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));
        } else {
            $image->expects($this->never())->method('setWidth');
            $image->expects($this->never())->method('setHeight');
            $image->expects($this->never())->method('hasBeenTransformed');
        }

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue($params));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImagick($imagick)->transform($event);
    }
}
