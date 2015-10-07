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

use Imbo\Image\Transformation\MaxSize,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\MaxSize
 * @group integration
 * @group transformations
 */
class MaxSizeTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new MaxSize();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getMaxSizeParams() {
        return [
            'landscape image with only width in params' => [
                'file' => FIXTURES_DIR . '/image.png',
                'params' => ['width' => 200],
                'width' => 665,
                'height' => 463,
                'transformedWidth' => 200,
                'transformedHeight' => 139,
            ],
            'landscape image with only height in params' => [
                'file' => FIXTURES_DIR . '/image.png',
                'params' => ['height' => 100],
                'width' => 665,
                'height' => 463,
                'transformedWidth' => 144,
                'transformedHeight' => 100,
            ],
            'landscape image both width and height in params' => [
                'file' => FIXTURES_DIR . '/image.png',
                'params' => ['width' => 100, 'height' => 100],
                'width' => 665,
                'height' => 463,
                'transformedWidth' => 100,
                'transformedHeight' => 70,
            ],
            'landscape image smaller than width and height params' => [
                'file' => FIXTURES_DIR . '/image.png',
                'params' => ['width' => 1000, 'height' => 1000],
                'width' => 665,
                'height' => 463,
                'transformedWidth' => null,
                'transformedHeight' => null,
                'transformation' => false,
            ],
            'portrait image with only width in params' => [
                'file' => FIXTURES_DIR . '/tall-image.png',
                'params' => ['width' => 200],
                'width' => 463,
                'height' => 665,
                'transformedWidth' => 200,
                'transformedHeight' => 287,
            ],
            'portrait image with only height in params' => [
                'file' => FIXTURES_DIR . '/tall-image.png',
                'params' => ['height' => 100],
                'width' => 463,
                'height' => 665,
                'transformedWidth' => 70,
                'transformedHeight' => 100,
            ],
            'portrait image both width and height in params' => [
                'file' => FIXTURES_DIR . '/tall-image.png',
                'params' => ['width' => 100, 'height' => 100],
                'width' => 463,
                'height' => 665,
                'transformedWidth' => 70,
                'transformedHeight' => 100,
            ],
            'portrait image smaller than width and height params' => [
                'file' => FIXTURES_DIR . '/tall-image.png',
                'params' => ['width' => 1000, 'height' => 1000],
                'width' => 463,
                'height' => 665,
                'transformedWidth' => null,
                'transformedHeight' => null,
                'transformation' => false,
            ],
        ];
    }

    /**
     * @dataProvider getMaxSizeParams
     * @covers Imbo\Image\Transformation\MaxSize::transform
     */
    public function testCanTransformImages($file, $params, $width, $height, $transformedWidth, $transformedHeight, $transformation = true) {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getWidth')->will($this->returnValue($width));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue($height));

        if ($transformation) {
            $image->expects($this->once())->method('setWidth')->with($transformedWidth)->will($this->returnValue($image));
            $image->expects($this->once())->method('setHeight')->with($transformedHeight)->will($this->returnValue($image));
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
        $imagick->readImageBlob(file_get_contents($file));

        $this->getTransformation()->setImagick($imagick)->transform($event);
    }
}
