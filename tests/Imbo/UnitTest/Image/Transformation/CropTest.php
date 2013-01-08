<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Image\Transformation;

use Imbo\Image\Transformation\Crop;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\Image\Transformation\Crop
 */
class CropTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Image\Transformation\Crop::__construct
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Missing required parameter: width
     */
    public function testThrowsExceptionWhenWidthIsMissing() {
        new Crop(array('height' => 123));
    }

    /**
     * @covers Imbo\Image\Transformation\Crop::__construct
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Missing required parameter: height
     */
    public function testThrowsExceptionWhenHeightIsMissing() {
        new Crop(array('width' => 123));
    }

    /**
     * Fetch different image parameters
     *
     * @return array[]
     */
    public function getImageParams() {
        return array(
            array(array('width' => 123, 'height' => 234), 123, 234),
            array(array('width' => 123, 'height' => 234, 'y' => 10), 123, 234, 0, 10),
            array(array('width' => 123, 'height' => 234, 'x' => 10, 'y' => 20), 123, 234, 10, 20),
        );
    }

    /**
     * @dataProvider getImageParams
     * @covers Imbo\Image\Transformation\Crop::__construct
     * @covers Imbo\Image\Transformation\Crop::applyToImage
     */
    public function testUsesAllParamsWithImagick($params, $width, $height, $x = 0, $y = 0) {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('originalimage'));
        $image->expects($this->once())->method('setBlob')->with('newimage')->will($this->returnSelf());
        $image->expects($this->once())->method('setWidth')->with($width)->will($this->returnSelf());
        $image->expects($this->once())->method('setHeight')->with($height)->will($this->returnSelf());

        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->once())->method('readImageBlob')->with('originalimage');
        $imagick->expects($this->once())->method('cropImage')->with($width, $height, $x, $y);
        $imagick->expects($this->once())->method('getImageBlob')->will($this->returnValue('newimage'));
        $imagick->expects($this->once())->method('getImageGeometry')->will($this->returnValue(array('width' => $width, 'height' => $height)));

        $crop = new Crop($params);
        $crop->setImagick($imagick)->applyToImage($image);
    }
}
