<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Model\Image;
use Imbo\Exception\TransformationException;
use PHPUnit\Framework\TestCase;
use Imagick;
use ImagickException;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Modulate
 */
class ModulateTest extends TestCase {
    public function getModulateParams() : array {
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
    public function testUsesDefaultValuesWhenParametersAreNotSpecified(array $params, int $brightness, int $saturation, int $hue) : void {
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('modulateImage')
            ->with($brightness, $saturation, $hue);

        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('hasBeenTransformed')
            ->with(true);

        (new Modulate())
            ->setImage($image)
            ->setImagick($imagick)
            ->transform($params);
    }

    public function testThrowsException() : void {
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('modulateImage')
            ->willThrowException($e = new ImagickException('some error'));

        $this->expectExceptionObject(new TransformationException('some error', 400, $e));

        (new Modulate())
            ->setImagick($imagick)
            ->transform([]);
    }
}
