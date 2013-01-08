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

use Imbo\Image\Transformation\Thumbnail;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 * @covers Imbo\Image\Transformation\Thumbnail
 */
class ThumbnailTest extends TransformationTests {
    /**
     * @var int
     */
    private $width = 80;

    /**
     * @var int
     */
    private $height = 90;

    /**
     * @var string
     */
    private $fit = 'outbound';

    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Thumbnail(array(
            'width' => $this->width,
            'height' => $this->height,
            'mode' => $this->fit,
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'thumbnail';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\Thumbnail::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with($this->width)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($this->height)->will($this->returnValue($image));

        return $image;
    }

    /**
     * @covers Imbo\Image\Transformation\Thumbnail::applyToImage
     */
    public function testApplyToImageUsingInsetMode() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with(20)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(13)->will($this->returnValue($image));

        $transformation = new Thumbnail(array(
            'width' => 20,
            'height' => 20,
            'fit' => 'inset',
        ));
        $transformation->applyToImage($image);
    }
}
