<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use ImagickPixelException;
use Imbo\Exception\TransformationException;
use Imbo\Image\InputSizeConstraint;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\Transformation\AutoRotate
 */
class AutoRotateTest extends TransformationTests
{
    protected function getTransformation(): AutoRotate
    {
        return new AutoRotate();
    }

    /**
     * @covers ::transform
     */
    public function testWillNotUpdateTheImageWhenNotNeeded(): void
    {
        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageOrientation' => 0,
        ]);
        $imagick
            ->expects($this->never())
            ->method('setImageOrientation');

        (new AutoRotate())
            ->setImagick($imagick)
            ->transform([]);
    }

    public function getTransformationData(): array
    {
        return [
            [
                Imagick::ORIENTATION_TOPRIGHT,
                Imagick::ORIENTATION_TOPLEFT,
                function (Imagick $imagick, Image $image): void {
                    $imagick
                        ->expects($this->once())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                },
            ],
            [
                Imagick::ORIENTATION_BOTTOMRIGHT,
                Imagick::ORIENTATION_TOPLEFT,
                function (Imagick $imagick, Image $image): void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                },
            ],
            [
                Imagick::ORIENTATION_BOTTOMLEFT,
                Imagick::ORIENTATION_TOPLEFT,
                function (Imagick $imagick, Image $image): void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->once())
                        ->method('flipImage');
                },
            ],
            [
                Imagick::ORIENTATION_LEFTTOP,
                Imagick::ORIENTATION_TOPLEFT,
                function (Imagick $imagick, Image $image): void {
                    $imagick
                        ->expects($this->once())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                    $imagick
                        ->expects($this->once())
                        ->method('getImageGeometry')
                        ->willReturn(['width' => 200, 'height' => 150]);

                    $image
                        ->expects($this->once())
                        ->method('setWidth')
                        ->with(200)
                        ->willReturnSelf();
                    $image
                        ->expects($this->once())
                        ->method('setHeight')
                        ->with(150)
                        ->willReturnSelf();
                },
            ],
            [
                Imagick::ORIENTATION_RIGHTTOP,
                Imagick::ORIENTATION_TOPLEFT,
                function (Imagick $imagick, Image $image): void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                    $imagick
                        ->expects($this->once())
                        ->method('getImageGeometry')
                        ->willReturn(['width' => 200, 'height' => 150]);

                    $image
                        ->expects($this->once())
                        ->method('setWidth')
                        ->with(200)
                        ->willReturnSelf();
                    $image
                        ->expects($this->once())
                        ->method('setHeight')
                        ->with(150)
                        ->willReturnSelf();
                },
            ],
            [
                Imagick::ORIENTATION_RIGHTBOTTOM,
                Imagick::ORIENTATION_TOPLEFT,
                function (Imagick $imagick, Image $image): void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->once())
                        ->method('flipImage');
                    $imagick
                        ->expects($this->once())
                        ->method('getImageGeometry')
                        ->willReturn(['width' => 200, 'height' => 150]);

                    $image
                        ->expects($this->once())
                        ->method('setWidth')
                        ->with(200)
                        ->willReturnSelf();
                    $image
                        ->expects($this->once())
                        ->method('setHeight')
                        ->with(150)
                        ->willReturnSelf();
                },
            ],
            [
                Imagick::ORIENTATION_LEFTBOTTOM,
                Imagick::ORIENTATION_TOPLEFT,
                function (Imagick $imagick, Image $image): void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                    $imagick
                        ->expects($this->once())
                        ->method('getImageGeometry')
                        ->willReturn(['width' => 200, 'height' => 150]);

                    $image
                        ->expects($this->once())
                        ->method('setWidth')
                        ->with(200)
                        ->willReturnSelf();
                    $image
                        ->expects($this->once())
                        ->method('setHeight')
                        ->with(150)
                        ->willReturnSelf();
                },
            ],
        ];
    }

    /**
     * @dataProvider getTransformationData
     * @covers ::transform
     */
    public function testWillRotateWhenNeeded(int $imageOrientation, int $newOrientation, callable $expectations): void
    {
        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageOrientation' => $imageOrientation,
        ]);

        $image = $this->createMock(Image::class);

        $expectations($imagick, $image);

        $imagick
            ->expects($this->once())
            ->method('setImageOrientation')
            ->with($newOrientation);

        (new AutoRotate())
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @covers ::getMinimumInputSize
     */
    public function testGetMinimumInputSizeStopsResolving(): void
    {
        $this->assertSame(InputSizeConstraint::STOP_RESOLVING, (new AutoRotate())->getMinimumInputSize([], []));
    }

    /**
     * @covers ::transform
     */
    public function testThrowsCustomExceptions()
    {
        $imagickException = new ImagickException('some error');
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('getImageOrientation')
            ->willThrowException($imagickException);

        $this->expectExceptionObject(new TransformationException('some error', 400, $imagickException));

        (new AutoRotate())
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsCustomExceptionsOnPixelException()
    {
        $pixelException = new ImagickPixelException('some error');
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('getImageOrientation')
            ->willThrowException($pixelException);

        $this->expectExceptionObject(new TransformationException('some error', 400, $pixelException));

        (new AutoRotate())
            ->setImagick($imagick)
            ->transform([]);
    }
}
