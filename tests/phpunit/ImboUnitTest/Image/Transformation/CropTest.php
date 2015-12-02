<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Crop;

/**
 * @covers Imbo\Image\Transformation\Crop
 * @group unit
 * @group transformations
 */
class CropTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Image\Transformation\Crop::transform
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Missing required parameter: width
     */
    public function testThrowsExceptionWhenWidthIsMissing() {
        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($this->getMock('Imbo\Model\Image')));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(['height' => 123]));

        $transformation = new Crop();
        $transformation->transform($event);
    }

    /**
     * @covers Imbo\Image\Transformation\Crop::transform
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Missing required parameter: height
     */
    public function testThrowsExceptionWhenHeightIsMissing() {
        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($this->getMock('Imbo\Model\Image')));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(['width' => 123]));

        $transformation = new Crop();
        $transformation->transform($event);
    }

    /**
     * Fetch different image parameters
     *
     * @return array[]
     */
    public function getImageParams() {
        return [
            'Do not perform work when cropping same sized images' => [['width' => 123, 'height' => 234], 123, 234, 123, 234, 0, 0, false],
            'Create new cropped image #1' => [['width' => 100, 'height' => 200, 'y' => 10], 100, 400, 100, 200, 0, 10],
            'Create new cropped image #2' => [['width' => 123, 'height' => 234, 'x' => 10, 'y' => 20], 200, 260, 123, 234, 10, 20],
        ];
    }

    /**
     * @dataProvider getImageParams
     * @covers Imbo\Image\Transformation\Crop::transform
     */
    public function testUsesAllParams($params, $originalWidth, $originalHeight, $width, $height, $x = 0, $y = 0, $shouldCrop = true) {
        $image = $this->getMock('Imbo\Model\Image');
        $imagick = $this->getMock('Imagick');

        $image->expects($this->any())->method('getWidth')->will($this->returnValue($originalWidth));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue($originalHeight));

        if ($shouldCrop) {
            $image->expects($this->once())->method('setWidth')->with($width)->will($this->returnSelf());
            $image->expects($this->once())->method('setHeight')->with($height)->will($this->returnSelf());

            $imagick->expects($this->once())->method('cropImage')->with($width, $height, $x, $y);
            $imagick->expects($this->once())->method('getImageGeometry')->will($this->returnValue(['width' => $width, 'height' => $height]));
        } else {
            $imagick->expects($this->never())->method('cropImage');
        }

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue($params));

        $crop = new Crop();
        $crop->setImagick($imagick)->transform($event);
    }

    /**
     * Fetch different invalid image parameters
     *
     * @return array[]
     */
    public function getInvalidImageParams() {
        return [
            'Dont throw if width/height are within bounds (no coords)' => [['width' => 100, 'height' => 100], 200, 200, false],
            'Dont throw if coords are within bounds' => [['width' => 100, 'height' => 100, 'x' => 100, 'y' => 100], 200, 200, false],
            'Throw if width is out of bounds'  => [['width' => 300, 'height' => 100], 200, 200, '#image width#i'],
            'Throw if height is out of bounds' => [['width' => 100, 'height' => 300], 200, 200, '#image height#i'],
            'Throw if X is out of bounds'  => [['width' => 100, 'height' => 100, 'x' => 500], 200, 200, '#image width#i'],
            'Throw if Y is out of bounds'  => [['width' => 100, 'height' => 100, 'y' => 500], 200, 200, '#image height#i'],
            'Throw if X + width is out of bounds'  => [['width' => 100, 'height' => 100, 'x' => 105], 200, 200, '#image width#i'],
            'Throw if Y + height is out of bounds' => [['width' => 100, 'height' => 100, 'y' => 105], 200, 200, '#image height#i'],
        ];
    }

    /**
     * @dataProvider getInvalidImageParams
     * @covers Imbo\Image\Transformation\Crop::transform
     */
    public function testThrowsOnInvalidCropParams($params, $originalWidth, $originalHeight, $errRegex) {
        $image = $this->getMock('Imbo\Model\Image');
        $imagick = $this->getMock('Imagick');

        $image->expects($this->any())->method('getWidth')->will($this->returnValue($originalWidth));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue($originalHeight));

        if ($errRegex) {
            $this->setExpectedExceptionRegExp('Imbo\Exception\TransformationException', $errRegex);
            $imagick->expects($this->never())->method('cropImage');
        } else {
            $image->expects($this->once())->method('setWidth')->will($this->returnSelf());
            $image->expects($this->once())->method('setHeight')->will($this->returnSelf());

            $imagick->expects($this->once())->method('cropImage');
        }

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue($params));

        $crop = new Crop();
        $crop->setImagick($imagick)->transform($event);
    }
}
