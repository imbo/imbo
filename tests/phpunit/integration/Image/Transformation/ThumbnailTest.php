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

use Imbo\Image\Transformation\Thumbnail;
use Imagick;

/**
 * @covers Imbo\Image\Transformation\Thumbnail
 * @group integration
 * @group transformations
 */
class ThumbnailTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Thumbnail();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getThumbnailParams() {
        return [
            'no params' => [
                'params' => [],
                'width'  => 50,
                'height' => 50
            ],
            'only width' => [
                'params' => ['width' => 60],
                'width'  => 60,
                'height' => 50
            ],
            'only height' => [
                'params' => ['height' => 60],
                'width'  => 50,
                'height' => 60
            ],
            'only fit (inset)' => [
                'params' => ['fit' => 'inset'],
                'width'  => 50,
                'height' => 34,
                'diff'   => 1,
            ],
            'only fit (outbound)' => [
                'params' => ['fit' => 'outbound'],
                'width'  => 50,
                'height' => 50
            ],
            'all params (inset)' => [
                'params' => ['width' => 123, 'height' => 456, 'fit' => 'inset'],
                'width'  => 123,
                'height' => 85,
                'diff'   => 1,
            ],
            'all params (outbound)' => [
                'params' => ['width' => 123, 'height' => 456, 'fit' => 'outbound'],
                'width'  => 123,
                'height' => 456
            ],
        ];
    }

    /**
     * @dataProvider getThumbnailParams
     * @covers Imbo\Image\Transformation\Thumbnail::transform
     */
    public function testCanTransformImage($params, $width, $height, $diff = 0) {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setWidth')->with($this->callback(function($setWidth) use ($width, $diff) {
            return $setWidth <= ($width + $diff) && $setWidth >= ($width - $diff);
        }))->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($this->callback(function($setHeight) use ($height, $diff) {
            return $setHeight <= ($height + $diff) && $setHeight >= ($height - $diff);
        }))->will($this->returnValue($image));
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform($params);
    }
}
