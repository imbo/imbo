<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Closure;
use Imagick;
use ImagickException;
use ImagickPixelException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputSizeConstraint;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(AutoRotate::class)]
class AutoRotateTest extends TransformationTests
{
    protected function getTransformation(): AutoRotate
    {
        return new AutoRotate();
    }

    public function testWillNotUpdateTheImageWhenNotNeeded(): void
    {
        /** @var Imagick&MockObject */
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

    #[DataProvider('getTransformationData')]
    public function testWillRotateWhenNeeded(int $imageOrientation, int $newOrientation, Closure $expectations): void
    {
        /** @var Imagick&MockObject */
        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageOrientation' => $imageOrientation,
        ]);

        $image = $this->createMock(Image::class);

        /** @var Closure */
        $new = $expectations->bindTo($this);
        $new($imagick, $image);

        $imagick
            ->expects($this->once())
            ->method('setImageOrientation')
            ->with($newOrientation);

        (new AutoRotate())
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([]);
    }

    public function testGetMinimumInputSizeStopsResolving(): void
    {
        $this->assertSame(InputSizeConstraint::STOP_RESOLVING, (new AutoRotate())->getMinimumInputSize([], []));
    }

    public function testThrowsCustomExceptions(): void
    {
        $imagickException = new ImagickException('some error');
        /** @var Imagick&MockObject */
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('getImageOrientation')
            ->willThrowException($imagickException);

        $this->expectExceptionObject(new TransformationException('some error', Response::HTTP_BAD_REQUEST, $imagickException));

        (new AutoRotate())
            ->setImagick($imagick)
            ->transform([]);
    }

    public function testThrowsCustomExceptionsOnPixelException(): void
    {
        $pixelException = new ImagickPixelException('some error');
        /** @var Imagick&MockObject */
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('getImageOrientation')
            ->willThrowException($pixelException);

        $this->expectExceptionObject(new TransformationException('some error', Response::HTTP_BAD_REQUEST, $pixelException));

        (new AutoRotate())
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @return array<array{imageOrientation:int,newOrientation:int,expectations:Closure}>
     */
    public static function getTransformationData(): array
    {
        return [
            [
                'imageOrientation' => Imagick::ORIENTATION_TOPRIGHT,
                'newOrientation' => Imagick::ORIENTATION_TOPLEFT,
                'expectations' => function (Imagick&MockObject $imagick, Image&MockObject $image): void {
                    /** @var AutoRotateTest $this */

                    $imagick
                        ->expects($this->once())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                },
            ],
            [
                'imageOrientation' => Imagick::ORIENTATION_BOTTOMRIGHT,
                'newOrientation' => Imagick::ORIENTATION_TOPLEFT,
                'expectations' => function (Imagick&MockObject $imagick, Image&MockObject $image): void {
                    /** @var AutoRotateTest $this */

                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                },
            ],
            [
                'imageOrientation' => Imagick::ORIENTATION_BOTTOMLEFT,
                'newOrientation' => Imagick::ORIENTATION_TOPLEFT,
                'expectations' => function (Imagick&MockObject $imagick, Image&MockObject $image): void {
                    /** @var AutoRotateTest $this */

                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->once())
                        ->method('flipImage');
                },
            ],
            [
                'imageOrientation' => Imagick::ORIENTATION_LEFTTOP,
                'newOrientation' => Imagick::ORIENTATION_TOPLEFT,
                'expectations' => function (Imagick&MockObject $imagick, Image&MockObject $image): void {
                    /** @var AutoRotateTest $this */

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
                'imageOrientation' => Imagick::ORIENTATION_RIGHTTOP,
                'newOrientation' => Imagick::ORIENTATION_TOPLEFT,
                'expectations' => function (Imagick&MockObject $imagick, Image&MockObject $image): void {
                    /** @var AutoRotateTest $this */

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
                'imageOrientation' => Imagick::ORIENTATION_RIGHTBOTTOM,
                'newOrientation' => Imagick::ORIENTATION_TOPLEFT,
                'expectations' => function (Imagick&MockObject $imagick, Image&MockObject $image): void {
                    /** @var AutoRotateTest $this */

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
                'imageOrientation' => Imagick::ORIENTATION_LEFTBOTTOM,
                'newOrientation' => Imagick::ORIENTATION_TOPLEFT,
                'expectations' => function (Imagick&MockObject $imagick, Image&MockObject $image): void {
                    /** @var AutoRotateTest $this */

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
}
