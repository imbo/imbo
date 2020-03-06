<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Model\Image;
use Imbo\Exception\TransformationException;
use Imagick;
use Imbo\EventManager\EventInterface;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Crop
 */
class CropTest extends TransformationTests {
    protected function getTransformation() : Crop {
        return new Crop();
    }

    public function getCropParams() : array {
        return [
            'cropped area smaller than the image' => [['width' => 100, 'height' => 50], 100, 50, true],
            'cropped area smaller than the image with x and y offset' => [['width' => 100, 'height' => 63, 'x' => 565, 'y' => 400], 100, 63, true],
            'center mode' => [['mode' => 'center', 'width' => 150, 'height' => 100], 150, 100, true],
            'center-x mode' => [['mode' => 'center-x', 'y' => 10, 'width' => 50, 'height' => 40], 50, 40, true],
            'center-y mode' => [['mode' => 'center-y', 'x' => 10, 'width' => 50, 'height' => 40], 50, 40, true],
        ];
    }

    /**
     * @dataProvider getCropParams
     * @covers ::transform
     */
    public function testCanCropImages(array $params, int $endWidth, int $endHeight, bool $transformed) : void {
        $image = $this->createConfiguredMock(Image::class, [
            'getWidth' => 665,
            'getHeight' => 463,
        ]);

        if ($transformed) {
            $image
                ->expects($this->once())
                ->method('setWidth')
                ->with($endWidth)
                ->willReturn($image);

            $image
                ->expects($this->once())
                ->method('setHeight')
                ->with($endHeight)
                ->willReturn($image);

            $image
                ->expects($this->once())
                ->method('hasBeenTransformed')
                ->with(true)
                ->willReturn($image);
        }

        $blob = file_get_contents(FIXTURES_DIR . '/image.png');
        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()
            ->setEvent($this->createMock(EventInterface::class))
            ->setImagick($imagick)
            ->setImage($image)
            ->transform($params);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionWhenWidthIsMissing() : void {
        $transformation = new Crop();
        $transformation->setImage($this->createMock(Image::class));
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: width',
            400
        ));
        $transformation->transform(['height' => 123]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionWhenHeightIsMissing() : void {
        $transformation = new Crop();
        $transformation->setImage($this->createMock(Image::class));
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: height',
            400
        ));
        $transformation->transform(['width' => 123]);
    }

    public function getImageParams() : array {
        return [
            'Do not perform work when cropping same sized images' => [
                ['width' => 123, 'height' => 234],
                123,
                234,
                123,
                234,
                0,
                0,
                false,
            ],
            'Create new cropped image #1' => [
                ['width' => 100, 'height' => 200, 'y' => 10],
                100,
                400,
                100,
                200,
                0,
                10,
            ],
            'Create new cropped image #2' => [
                ['width' => 123, 'height' => 234, 'x' => 10, 'y' => 20],
                200,
                260,
                123,
                234,
                10,
                20,
            ],
        ];
    }

    /**
     * @dataProvider getImageParams
     * @covers ::transform
     */
    public function testUsesAllParams(array $params, int $originalWidth, int $originalHeight, int $width, int $height, int $x, int $y, ?bool $shouldCrop = true) : void {
        $imagick = $this->createMock(Imagick::class);
        $image = $this->createConfiguredMock(Image::class, [
            'getWidth'  => $originalWidth,
            'getHeight' => $originalHeight,
        ]);

        if ($shouldCrop) {
            $image
                ->expects($this->once())
                ->method('setWidth')
                ->with($width)
                ->willReturnSelf();
            $image
                ->expects($this->once())
                ->method('setHeight')
                ->with($height)
                ->willReturnSelf();

            $imagick
                ->expects($this->once())
                ->method('cropImage')
                ->with($width, $height, $x, $y);
            $imagick
                ->expects($this->once())
                ->method('getImageGeometry')
                ->willReturn(['width' => $width, 'height' => $height]);
        } else {
            $imagick
                ->expects($this->never())
                ->method('cropImage');
        }

        (new Crop())
            ->setImagick($imagick)
            ->setImage($image)
            ->transform($params);
    }

    public function getInvalidImageParams() : array {
        return [
            'Dont throw if width/height are within bounds (no coords)' => [
                ['width' => 100, 'height' => 100],
                200,
                200,
            ],
            'Dont throw if coords are within bounds' => [
                ['width' => 100, 'height' => 100, 'x' => 100, 'y' => 100],
                200,
                200,
            ],
            'Throw if width is out of bounds'  => [
                ['width' => 300, 'height' => 100],
                200,
                200,
                '#image width#i',
            ],
            'Throw if height is out of bounds' => [
                ['width' => 100, 'height' => 300],
                200,
                200,
                '#image height#i',
            ],
            'Throw if X is out of bounds'  => [
                ['width' => 100, 'height' => 100, 'x' => 500],
                200,
                200,
                '#image width#i',
            ],
            'Throw if Y is out of bounds'  => [
                ['width' => 100, 'height' => 100, 'y' => 500],
                200,
                200,
                '#image height#i',
            ],
            'Throw if X + width is out of bounds'  => [
                ['width' => 100, 'height' => 100, 'x' => 105],
                200,
                200,
                '#image width#i',
            ],
            'Throw if Y + height is out of bounds' => [
                ['width' => 100, 'height' => 100, 'y' => 105],
                200,
                200,
                '#image height#i',
            ],
        ];
    }

    /**
     * @dataProvider getInvalidImageParams
     * @covers ::transform
     */
    public function testThrowsOnInvalidCropParams(array $params, int $originalWidth, int $originalHeight, ?string $errRegex = null) : void {
        $imagick = $this->createMock(Imagick::class);
        $image = $this->createConfiguredMock(Image::class, [
            'getWidth'  => $originalWidth,
            'getHeight' => $originalHeight,
        ]);

        if (null !== $errRegex) {
            $this->expectException(TransformationException::class);
            $this->expectExceptionCode(400);
            $this->expectExceptionMessageRegExp($errRegex);
            $imagick->expects($this->never())->method('cropImage');
        } else {
            $image
                ->expects($this->once())
                ->method('setWidth')
                ->willReturnSelf();
            $image
                ->expects($this->once())
                ->method('setHeight')
                ->willReturnSelf();

            $imagick->expects($this->once())->method('cropImage');
        }

        (new Crop())
            ->setImagick($imagick)
            ->setImage($image)
            ->transform($params);
    }
}