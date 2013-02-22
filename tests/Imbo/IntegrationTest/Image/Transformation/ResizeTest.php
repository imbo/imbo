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

use Imbo\Image\Transformation\Resize;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class ResizeTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Resize(array(
            'width' => 200,
            'height' => 100,
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'resize';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\Resize::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with(200)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(100)->will($this->returnValue($image));

        return $image;
    }

    /**
     * @covers Imbo\Image\Transformation\Resize::applyToImage
     */
    public function testApplyToImageWithOnlyWidth() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(665));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(463));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with(200)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($this->isType('int'))->will($this->returnValue($image));

        $transformation = new Resize(array('width' => 200));
        $transformation->applyToImage($image);
    }

    /**
     * @covers Imbo\Image\Transformation\Resize::applyToImage
     */
    public function testApplyToImageWithOnlyHeight() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(665));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(463));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with($this->isType('int'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(200)->will($this->returnValue($image));

        $transformation = new Resize(array('height' => 200));
        $transformation->applyToImage($image);
    }
}
