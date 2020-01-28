<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Image\Transformation\Border;
use Imagick;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Border
 */
class BorderTest extends TransformationTests {
    protected function getTransformation() : Border {
        return new Border();
    }

    public function getBorderParams() : array {
        return [
            'inline border' => [665, 463, 3, 4, 'inset'],
            'outbound border' => [671, 471, 3, 4, 'outbound'],
        ];
    }

    /**
     * @dataProvider getBorderParams
     */
    public function testTransformationSupportsDifferentModes(int $expectedWidth, int $expectedHeight, int $borderWidth, int $borderHeight, string $borderMode) : void {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setWidth')
            ->with($expectedWidth)
            ->willReturn($image);

        $image
            ->expects($this->once())
            ->method('setHeight')
            ->with($expectedHeight)
            ->willReturn($image);

        $image
            ->expects($this->once())
            ->method('hasBeenTransformed')
            ->with(true);

        $blob = file_get_contents(FIXTURES_DIR . '/image.png');

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()
            ->setImagick($imagick)
            ->setImage($image)
            ->transform([
                'color' => 'white',
                'width' => $borderWidth,
                'height' => $borderHeight,
                'mode' => $borderMode,
            ]);
    }
}
