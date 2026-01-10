<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Modulate::class)]
class ModulateTest extends TransformationTests
{
    protected function getTransformation(): Modulate
    {
        return new Modulate();
    }

    #[DataProvider('getModulateParamsForTransformation')]
    public function testCanModulateImages(array $params): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturn($image);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR.'/image.png'));

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform($params);
    }

    #[DataProvider('getModulateParams')]
    public function testUsesDefaultValuesWhenParametersAreNotSpecified(array $params, int $brightness, int $saturation, int $hue): void
    {
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('modulateImage')
            ->with($brightness, $saturation, $hue);

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

    public function testThrowsException(): void
    {
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
