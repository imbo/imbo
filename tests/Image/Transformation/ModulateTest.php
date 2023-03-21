<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Modulate
 */
class ModulateTest extends TransformationTests
{
    protected function getTransformation(): Modulate
    {
        return new Modulate();
    }

    public static function getModulateParamsForTransformation(): array
    {
        return [
            'no params' => [
                [],
            ],
            'some params' => [
                ['b' => 10, 's' => 10],
            ],
            'all params' => [
                ['b' => 1, 's' => 2, 'h' => 3],
            ],
        ];
    }

    /**
     * @dataProvider getModulateParamsForTransformation
     * @covers ::transform
     */
    public function testCanModulateImages(array $params): void
    {
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

    public static function getModulateParams(): array
    {
        return [
            'no params' => [
                [], 100, 100, 100,
            ],
            'some params' => [
                ['b' => 10, 's' => 50], 10, 50, 100,
            ],
            'all params' => [
                ['b' => 1, 's' => 2, 'h' => 3], 1, 2, 3,
            ],
        ];
    }

    /**
     * @dataProvider getModulateParams
     * @covers ::transform
     */
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

    /**
     * @covers ::transform
     */
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
}
