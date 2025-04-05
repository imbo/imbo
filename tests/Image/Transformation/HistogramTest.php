<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(Histogram::class)]
class HistogramTest extends TransformationTests
{
    protected function getTransformation(): Histogram
    {
        return new Histogram();
    }

    #[DataProvider('getHistogramParameters')]
    public function testTransformWithDifferentParameters(int $scale, int $resultingWidth): void
    {
        $blob = file_get_contents(FIXTURES_DIR . '/512x512.png');

        /** @var Image&MockObject */
        $image = $this->createConfiguredMock(Image::class, [
            'getBlob' => $blob,
            'getWidth' => 512,
            'getHeight' => 512,
            'getExtension' => 'png',
        ]);

        $image
            ->expects($this->once())
            ->method('setWidth')
            ->with($resultingWidth)
            ->willReturn($image);

        $image
            ->expects($this->once())
            ->method('setHeight')
            ->willReturn($image);

        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([
                'scale' => $scale,
            ]);
    }

    /**
     * @return array<array{scale:int,resultingWidth:int}>
     */
    public static function getHistogramParameters(): array
    {
        return [
            [
                'scale' => 1,
                'resultingWidth' => 256,
            ],
            [
                'scale' => 2,
                'resultingWidth' => 512,
            ],
            [
                'scale' => 4,
                'resultingWidth' => 1024,
            ],
            [
                'scale' => 8,
                'resultingWidth' => 2048,
            ],
        ];
    }
}
