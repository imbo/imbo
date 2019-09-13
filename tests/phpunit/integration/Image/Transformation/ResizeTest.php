<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Resize;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Resize
 */
class ResizeTest extends TransformationTests {
    protected function getTransformation() : Resize {
        return new Resize();
    }

    public function getResizeParams() : array {
        return [
            'only width' => [
                'params'         => ['width' => 100],
                'transformation' => true,
                'resizedWidth'   => 100,
                'resizedHeight'  => 70,
            ],
            'only height' => [
                'params'         => ['height' => 100],
                'transformation' => true,
                'resizedWidth'   => 144,
                'resizedHeight'  => 100,
            ],
            'width and height' => [
                'params'         => ['width' => 100, 'height' => 200],
                'transformation' => true,
                'resizedWidth'   => 100,
                'resizedHeight'  => 200,
            ],
            'params match image size' => [
                'params'         => ['width' => 665, 'height' => 463],
                'transformation' => false
            ],
        ];
    }

    /**
     * @dataProvider getResizeParams
     * @covers ::transform
     */
    public function testCanTransformImage(array $params, bool $transformation, int $resizedWidth = null, int $resizedHeight = null) : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));

        if ($transformation) {
            $image->expects($this->once())->method('setWidth')->with($resizedWidth)->will($this->returnValue($image));
            $image->expects($this->once())->method('setHeight')->with($resizedHeight)->will($this->returnValue($image));
            $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));
        } else {
            $image->expects($this->never())->method('setWidth');
            $image->expects($this->never())->method('setHeight');
            $image->expects($this->never())->method('hasBeenTransformed');
        }

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform($params);
    }
}
