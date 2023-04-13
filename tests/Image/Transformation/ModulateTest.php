<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Modulate
 */
class ModulateTest extends TransformationTests
{
    protected function getTransformation(): Modulate
    {
        return new Modulate();
    }

    /**
     * @dataProvider getModulateParamsForTransformation
     * @covers ::transform
     */
    public function testCanModulateImages(array $params): void
    {
        /** @var Image&MockObject */
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturn($image);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform($params);
    }

    /**
     * @dataProvider getModulateParams
     * @covers ::transform
     */
    public function testUsesDefaultValuesWhenParametersAreNotSpecified(array $params, int $brightness, int $saturation, int $hue): void
    {
        /** @var Imagick&MockObject */
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('modulateImage')
            ->with($brightness, $saturation, $hue);

        /** @var Image&MockObject */
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        (new Modulate())
            ->setImage($image)
            ->setImagick($imagick)
            ->transform($params);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsException(): void
    {
        /** @var Imagick&MockObject */
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('modulateImage')
            ->willThrowException($e = new ImagickException('some error'));

        $this->expectExceptionObject(new TransformationException('some error', Response::HTTP_BAD_REQUEST, $e));

        (new Modulate())
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @return array<string,array{params:array}>
     */
    public static function getModulateParamsForTransformation(): array
    {
        return [
            'no params' => [
                'params' => [],
            ],
            'some params' => [
                'params' => ['b' => 10, 's' => 10],
            ],
            'all params' => [
                'params' => ['b' => 1, 's' => 2, 'h' => 3],
            ],
        ];
    }

    /**
     * @return array<string,array{params:array,brightness:int,saturation:int,hue:int}>
     */
    public static function getModulateParams(): array
    {
        return [
            'no params' => [
                'params' => [],
                'brightness' => 100,
                'saturation' => 100,
                'hue' => 100,
            ],
            'some params' => [
                'params' => ['b' => 10, 's' => 50],
                'brightness' => 10,
                'saturation' => 50,
                'hue' => 100,
            ],
            'all params' => [
                'params' => ['b' => 1, 's' => 2, 'h' => 3],
                'brightness' => 1,
                'saturation' => 2,
                'hue' => 3,
            ],
        ];
    }
}
