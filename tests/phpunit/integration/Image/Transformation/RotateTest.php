<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Rotate;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Rotate
 */
class RotateTest extends TransformationTests {
    protected function getTransformation() : Rotate {
        return new Rotate();
    }

    public function getRotateParams() : array {
        return [
            '90 angle' => [90, 463, 665],
            '180 angle' => [180, 665, 463],
        ];
    }

    /**
     * @dataProvider getRotateParams
     * @covers ::transform
     */
    public function testCanTransformImage(int $angle, int $width, int $height) : void {
        $image = $this->createMock('Imbo\Model\Image');

        $image->expects($this->once())->method('setWidth')->with($width)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($height)->will($this->returnValue($image));
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([
            'angle' => $angle,
            'bg' => 'fff',
        ]);
    }
}
