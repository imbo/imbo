<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Transformation
 */
class TransformationTest extends TestCase {
    private $transformation;

    public function setUp() : void {
        $this->transformation = new Border();
    }

    public function getColors() : array {
        return [
            ['red', 'red'],
            ['000', '#000'],
            ['000000', '#000000'],
            ['fff', '#fff'],
            ['FFF', '#FFF'],
            ['FFF000', '#FFF000'],
            ['#FFF', '#FFF'],
            ['#FFF000', '#FFF000'],
        ];
    }

    /**
     * @dataProvider getColors
     * @covers ::formatColor
     * @covers ::setImage
     * @covers ::setImagick
     */
    public function testCanFormatColors(string $color, string $expected) : void {
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
}
