<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Rotate;
use Imagick;

/**
 * @covers Imbo\Image\Transformation\Rotate
 * @group integration
 * @group transformations
 */
class RotateTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Rotate();
    }

    public function getRotateParams() {
        return [
            '90 angle' => [90, 463, 665],
            '180 angle' => [180, 665, 463],
        ];
    }

    /**
     * @dataProvider getRotateParams
     * @covers Imbo\Image\Transformation\Rotate::transform
     */
    public function testCanTransformImage($angle, $width, $height) {
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
