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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class CropTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Image\Transformation\Crop::applyToImage
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Missing required parameter: width
     */
    public function testThrowsExceptionWhenWidthIsMissing() {
        $transformation = new Crop();
        $transformation->applyToImage($this->getMock('Imbo\Model\Image'), array('height' => 123));
    }

    /**
     * @covers Imbo\Image\Transformation\Crop::applyToImage
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Missing required parameter: height
     */
    public function testThrowsExceptionWhenHeightIsMissing() {
        $transformation = new Crop();
        $transformation->applyToImage($this->getMock('Imbo\Model\Image'), array('width' => 123));
    }

    /**
     * Fetch different image parameters
     *
     * @return array[]
     */
    public function getImageParams() {
        return array(
            'Do not perform work when cropping same sized images' => array(array('width' => 123, 'height' => 234), 123, 234, 0, 0, false),
            'Do not perform work when getting a larger crop than image' => array(array('width' => 5123, 'height' => 5234), 123, 234, 0, 0, false),
            'Create new cropped image #1' => array(array('width' => 123, 'height' => 234, 'y' => 10), 123, 234, 0, 10),
            'Create new cropped image #2' => array(array('width' => 123, 'height' => 234, 'x' => 10, 'y' => 20), 123, 234, 10, 20),
        );
    }

    /**
     * @dataProvider getImageParams
     * @covers Imbo\Image\Transformation\Crop::applyToImage
     */
    public function testUsesAllParamsWithImagick($params, $width, $height, $x = 0, $y = 0, $shouldCrop = true) {
        $image = $this->getMock('Imbo\Model\Image');
        $imagick = $this->getMock('Imagick');

        $image->expects($this->any())->method('getWidth')->will($this->returnValue($width));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue($height));

        if ($shouldCrop) {
            $image->expects($this->once())->method('getBlob')->will($this->returnValue('originalimage'));
            $image->expects($this->once())->method('setBlob')->with('newimage')->will($this->returnSelf());
            $image->expects($this->once())->method('setWidth')->with($width)->will($this->returnSelf());
            $image->expects($this->once())->method('setHeight')->with($height)->will($this->returnSelf());

            $imagick->expects($this->once())->method('readImageBlob')->with('originalimage');
            $imagick->expects($this->once())->method('cropImage')->with($width, $height, $x, $y);
            $imagick->expects($this->once())->method('getImageBlob')->will($this->returnValue('newimage'));
            $imagick->expects($this->once())->method('getImageGeometry')->will($this->returnValue(array('width' => $width, 'height' => $height)));
        } else {
            $imagick->expects($this->never())->method('cropImage');
        }

        $crop = new Crop();
        $crop->setImagick($imagick)->applyToImage($image, $params);
    }
}
