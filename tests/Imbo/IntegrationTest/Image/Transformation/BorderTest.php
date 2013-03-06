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

use Imbo\Image\Transformation\Border;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class BorderTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Border(array('color' => 'ffffff', 'width' => 3, 'height' => 4));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'border';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\Border::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with(671)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(471)->will($this->returnValue($image));

        return $image;
    }

    /**
     * @covers Imbo\Image\Transformation\Border::__construct
     * @covers Imbo\Image\Transformation\Border::applyToImage
     */
    public function testTransformationSupportsDifferentModes() {
        $imagePath = FIXTURES_DIR . '/image.png';

        $size = getimagesize($imagePath);

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents($imagePath)));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with($size[0])->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($size[1])->will($this->returnValue($image));

        $transformation = new Border(array('color' => 'white', 'width' => 3, 'height' => 4, 'mode' => 'inline'));
        $transformation->applyToImage($image);
    }
}
