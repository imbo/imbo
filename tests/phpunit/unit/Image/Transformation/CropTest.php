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
use Imbo\Exception\TransformationException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Image\Transformation\Crop
 * @group unit
 * @group transformations
 */
class CropTest extends TestCase {
    /**
     * @covers Imbo\Image\Transformation\Crop::transform
     */
    public function testThrowsExceptionWhenWidthIsMissing() {
        $transformation = new Crop();
        $transformation->setImage($this->createMock('Imbo\Model\Image'));
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: width',
            400
        ));
        $transformation->transform(['height' => 123]);
    }

    /**
     * @covers Imbo\Image\Transformation\Crop::transform
     */
    public function testThrowsExceptionWhenHeightIsMissing() {
        $transformation = new Crop();
        $transformation->setImage($this->createMock('Imbo\Model\Image'));
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: height',
            400
        ));
        $transformation->transform(['width' => 123]);
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
        $image = $this->createMock('Imbo\Model\Image');
        $imagick = $this->createMock('Imagick');

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

        $crop = new Crop();
        $crop->setImagick($imagick)->setImage($image)->transform($params);
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
        $image = $this->createMock('Imbo\Model\Image');
        $imagick = $this->createMock('Imagick');

        $image->expects($this->any())->method('getWidth')->will($this->returnValue($originalWidth));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue($originalHeight));

        if ($errRegex) {
            $this->expectException('Imbo\Exception\TransformationException');
            $this->expectExceptionMessageRegExp($errRegex);
            $imagick->expects($this->never())->method('cropImage');
        } else {
            $image->expects($this->once())->method('setWidth')->will($this->returnSelf());
            $image->expects($this->once())->method('setHeight')->will($this->returnSelf());

            $imagick->expects($this->once())->method('cropImage');
        }

        $crop = new Crop();
        $crop->setImagick($imagick)->setImage($image)->transform($params);
    }
}
