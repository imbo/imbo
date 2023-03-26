<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Transformation
 */
class TransformationTest extends TestCase
{
    private Border $transformation;

    public function setUp(): void
    {
        $this->transformation = new Border();
    }

    /**
     * @dataProvider getColors
     * @covers ::formatColor
     * @covers ::setImage
     * @covers ::setImagick
     */
    public function testCanFormatColors(string $color, string $expected): void
    {
        /** @var Image&MockObject */
        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('setWidth')->willReturnSelf();
        $image->expects($this->once())->method('setHeight')->willReturnSelf();

        /** @var Imagick&MockObject */
        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageGeometry' => [
                'width'  => 100,
                'height' => 100,
            ],
            'getImageAlphaChannel' => false,
        ]);

        $imagick
            ->expects($this->once())
            ->method('borderImage')
            ->with($expected, 1, 1);

        $this->transformation
            ->setImage($image)
            ->setImagick($imagick)
            ->transform(['color' => $color]);
    }

    /**
     * @return array<array{color:string,expected:string}>
     */
    public static function getColors(): array
    {
        return [
            [
                'color' => 'red',
                'expected' => 'red',
            ],
            [
                'color' => '000',
                'expected' => '#000',
            ],
            [
                'color' => '000000',
                'expected' => '#000000',
            ],
            [
                'color' => 'fff',
                'expected' => '#fff',
            ],
            [
                'color' => 'FFF',
                'expected' => '#FFF',
            ],
            [
                'color' => 'FFF000',
                'expected' => '#FFF000',
            ],
            [
                'color' => '#FFF',
                'expected' => '#FFF',
            ],
            [
                'color' => '#FFF000',
                'expected' => '#FFF000',
            ],
        ];
    }
}
