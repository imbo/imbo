<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Border
 */
class BorderTest extends TransformationTests
{
    protected function getTransformation(): Border
    {
        return new Border();
    }

    /**
     * @dataProvider getBorderParams
     * @covers ::transform
     */
    public function testTransformationSupportsDifferentModes(int $expectedWidth, int $expectedHeight, int $borderWidth, int $borderHeight, string $borderMode): void
    {
        /** @var Image&MockObject */
        $image = $this->createConfiguredMock(Image::class, [
            'getWidth' => $expectedWidth,
            'getHeight' => $expectedHeight,
        ]);
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
            ->method('setHasBeenTransformed')
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

    /**
     * @return array<string,array{expectedWidth:int,expectedHeight:int,borderWidth:int,borderHeight:int,borderMode:string}>
     */
    public static function getBorderParams(): array
    {
        return [
            'inline border' => [
                'expectedWidth' => 665,
                'expectedHeight' => 463,
                'borderWidth' => 3,
                'borderHeight' => 4,
                'borderMode' => 'inset',
            ],
            'outbound border' => [
                'expectedWidth' => 671,
                'expectedHeight' => 471,
                'borderWidth' => 3,
                'borderHeight' => 4,
                'borderMode' => 'outbound',
            ],
        ];
    }
}
