<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Crop;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Crop
 */
class CropTest extends TransformationTests {
    protected function getTransformation() : Crop {
        return new Crop();
    }

    public function getCropParams() : array {
        return [
            'cropped area smaller than the image' => [['width' => 100, 'height' => 50], 100, 50, true],
            'cropped area smaller than the image with x and y offset' => [['width' => 100, 'height' => 63, 'x' => 565, 'y' => 400], 100, 63, true],
            'center mode' => [['mode' => 'center', 'width' => 150, 'height' => 100], 150, 100, true],
            'center-x mode' => [['mode' => 'center-x', 'y' => 10, 'width' => 50, 'height' => 40], 50, 40, true],
            'center-y mode' => [['mode' => 'center-y', 'x' => 10, 'width' => 50, 'height' => 40], 50, 40, true],
        ];
    }

    /**
     * @dataProvider getCropParams
     */
    public function testCanCropImages(array $params, int $endWidth, int $endHeight, bool $transformed) : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(463));

        if ($transformed) {
            $image->expects($this->once())->method('setWidth')->with($endWidth)->will($this->returnValue($image));
            $image->expects($this->once())->method('setHeight')->with($endHeight)->will($this->returnValue($image));
            $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));
        }

        $blob = file_get_contents(FIXTURES_DIR . '/image.png');
        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()
             ->setEvent($this->createMock('Imbo\EventManager\Event'))
             ->setImagick($imagick)
             ->setImage($image)
             ->transform($params);
    }
}
