<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Canvas
 */
class CanvasTest extends TransformationTests
{
    protected function getTransformation(): Canvas
    {
        return new Canvas();
    }

    /**
     * @dataProvider getCanvasParameters
     * @covers ::transform
     */
    public function testTransformWithDifferentParameters(?int $width, ?int $height, string $mode, int $resultingWidth, int $resultingHeight): void
    {
        $blob = file_get_contents(FIXTURES_DIR . '/image.png');

        /** @var Image&MockObject */
        $image = $this->createConfiguredMock(Image::class, [
            'getBlob' => $blob,
            'getWidth' => 665,
            'getHeight' => 463,
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
            ->with($resultingHeight)
            ->willReturn($image);

        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()
            ->setImagick($imagick)
            ->setImage($image)
            ->transform([
                'width' => $width,
                'height' => $height,
                'mode' => $mode,
            ]);
    }

    /**
     * @return array<string,array{width:?int,height:?int,mode:string,resultingWidth:int,resultingHeight:int}>
     */
    public static function getCanvasParameters(): array
    {
        return [
            'free mode with only width' => [
                'width' => 1000,
                'height' => null,
                'mode' => 'free',
                'resultingWidth' => 1000,
                'resultingHeight' => 463,
            ],
            'free mode with only height' => [
                'width' => null,
                'height' => 1000,
                'mode' => 'free',
                'resultingWidth' => 665,
                'resultingHeight' => 1000,
            ],
            'free mode where both sides are smaller than the original' => [
                'width' => 200,
                'height' => 200,
                'mode' => 'free',
                'resultingWidth' => 200,
                'resultingHeight' => 200,
            ],
            'free mode where height is smaller than the original' => [
                'width' => 1000,
                'height' => 200,
                'mode' => 'free',
                'resultingWidth' => 1000,
                'resultingHeight' => 200,
            ],
            'free mode where width is smaller than the original' => [
                'width' => 200,
                'height' => 1000,
                'mode' => 'free',
                'resultingWidth' => 200,
                'resultingHeight' => 1000,
            ],
            'center' => [
                'width' => 1000,
                'height' => 1000,
                'mode' => 'center',
                'resultingWidth' => 1000,
                'resultingHeight' => 1000,
            ],
            'center-x' => [
                'width' => 1000,
                'height' => 1000,
                'mode' => 'center-x',
                'resultingWidth' => 1000,
                'resultingHeight' => 1000,
            ],
            'center-y' => [
                'width' => 1000,
                'height' => 1000,
                'mode' => 'center-y',
                'resultingWidth' => 1000,
                'resultingHeight' => 1000,
            ],
            '#1 center mode where one of the sides are smaller than the original' => [
                'width' => 1000,
                'height' => 200,
                'mode' => 'center',
                'resultingWidth' => 1000,
                'resultingHeight' => 200,
            ],
            '#2 center mode where one of the sides are smaller than the original' => [
                'width' => 200,
                'height' => 1000,
                'mode' => 'center',
                'resultingWidth' => 200,
                'resultingHeight' => 1000,
            ],
            'center-x mode where one of the sides are smaller than the original' =>  [
                'width' => 1000,
                'height' => 200,
                'mode' => 'center-x',
                'resultingWidth' => 1000,
                'resultingHeight' => 200,
            ],
            'center-y mode where one of the sides are smaller than the original' =>  [
                'width' => 1000,
                'height' => 200,
                'mode' => 'center-y',
                'resultingWidth' => 1000,
                'resultingHeight' => 200,
            ],
            'center mode where both sides are smaller than the original' => [
                'width' => 200,
                'height' => 200,
                'mode' => 'center',
                'resultingWidth' => 200,
                'resultingHeight' => 200,
            ],
            'center-x mode where both sides are smaller than the original' => [
                'width' => 200,
                'height' => 200,
                'mode' => 'center-x',
                'resultingWidth' => 200,
                'resultingHeight' => 200,
            ],
            'center-y mode where both sides are smaller than the original' => [
                'width' => 200,
                'height' => 200,
                'mode' => 'center-y',
                'resultingWidth' => 200,
                'resultingHeight' => 200,
            ],
        ];
    }
}
