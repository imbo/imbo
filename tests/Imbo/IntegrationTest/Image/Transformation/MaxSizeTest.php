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

use Imbo\Image\Transformation\MaxSize;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 * @covers Imbo\Image\Transformation\MaxSize
 */
class MaxSizeTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new MaxSize(array('width' => 200, 'height' => 100));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'maxsize';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->once())->method('setWidth')->with(144)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(100)->will($this->returnValue($image));

        return $image;
    }

    /**
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    public function testApplyToImageWithOnlyWidth() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->once())->method('setWidth')->with(200)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(139)->will($this->returnValue($image));

        $transformation = new MaxSize(array('width' => 200));
        $transformation->applyToImage($image);
    }

    /**
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    public function testApplyToImageWithOnlyHeight() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->once())->method('setWidth')->with(287)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(200)->will($this->returnValue($image));

        $transformation = new MaxSize(array('height' => 200));
        $transformation->applyToImage($image);
    }

    /**
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    public function testApplyToTallImage() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/tall-image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(463));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(665));
        $image->expects($this->once())->method('setWidth')->with(70)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(100)->will($this->returnValue($image));

        $transformation = new MaxSize(array('width' => 200, 'height' => 100));
        $transformation->applyToImage($image);
    }

    /**
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    public function testApplyToImageSmallerThanParams() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(463));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(665));
        $image->expects($this->never())->method('setBlob');
        $image->expects($this->never())->method('setWidth');
        $image->expects($this->never())->method('setHeight');

        $transformation = new MaxSize(array('width' => 1000, 'height' => 1000));
        $transformation->applyToImage($image);
    }
}
