<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Transformation::class)]
class TransformationTest extends TestCase
{
    private Border $transformation;

    public function setUp(): void
    {
        $this->transformation = new Border();
    }

    #[DataProvider('getColors')]
    public function testCanFormatColors(string $color, string $expected): void
    {
        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('setWidth')->willReturnSelf();
        $image->expects($this->once())->method('setHeight')->willReturnSelf();

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
