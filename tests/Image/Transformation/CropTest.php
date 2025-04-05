<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(Crop::class)]
class CropTest extends TransformationTests
{
    protected function getTransformation(): Crop
    {
        return new Crop();
    }

    #[DataProvider('getCropParams')]
    public function testCanCropImages(array $params, int $endWidth, int $endHeight, bool $transformed): void
    {
        /** @var Image&MockObject */
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
                ->method('setHasBeenTransformed')
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

    public function testThrowsExceptionWhenWidthIsMissing(): void
    {
        $transformation = new Crop();
        $transformation->setImage($this->createMock(Image::class));
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: width',
            Response::HTTP_BAD_REQUEST,
        ));
        $transformation->transform(['height' => 123]);
    }

    public function testThrowsExceptionWhenHeightIsMissing(): void
    {
        $transformation = new Crop();
        $transformation->setImage($this->createMock(Image::class));
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: height',
            Response::HTTP_BAD_REQUEST,
        ));
        $transformation->transform(['width' => 123]);
    }

    #[DataProvider('getImageParams')]
    public function testUsesAllParams(array $params, int $originalWidth, int $originalHeight, int $width, int $height, int $x, int $y, bool $shouldCrop): void
    {
        /** @var Imagick&MockObject */
        $imagick = $this->createMock(Imagick::class);

        /** @var Image&MockObject */
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

    #[DataProvider('getInvalidImageParams')]
    public function testThrowsOnInvalidCropParams(array $params, int $originalWidth, int $originalHeight, ?string $errRegex = null): void
    {
        /** @var Imagick&MockObject */
        $imagick = $this->createMock(Imagick::class);

        /** @var Image&MockObject */
        $image = $this->createConfiguredMock(Image::class, [
            'getWidth'  => $originalWidth,
            'getHeight' => $originalHeight,
        ]);

        if (null !== $errRegex) {
            $this->expectException(TransformationException::class);
            $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
            $this->expectExceptionMessageMatches($errRegex);
            $imagick
                ->expects($this->never())
                ->method('cropImage');
        } else {
            $image
                ->expects($this->once())
                ->method('setWidth')
                ->with($params['width'])
                ->willReturnSelf();
            $image
                ->expects($this->once())
                ->method('setHeight')
                ->with($params['height'])
                ->willReturnSelf();

            $imagick
                ->expects($this->once())
                ->method('cropImage');
            $imagick
                ->expects($this->once())
                ->method('getImageGeometry')
                ->willReturn([
                    'width'  => $params['width'],
                    'height' => $params['height'],
                ]);
        }

        (new Crop())
            ->setImagick($imagick)
            ->setImage($image)
            ->transform($params);
    }

    /**
     * @return array<string,array{params:array<string,int|string>,endWidth:int,endHeight:int,transformed:bool}>
     */
    public static function getCropParams(): array
    {
        return [
            'cropped area smaller than the image' => [
                'params' => ['width' => 100, 'height' => 50],
                'endWidth' => 100,
                'endHeight' => 50,
                'transformed' => true,
            ],
            'cropped area smaller than the image with x and y offset' => [
                'params' => ['width' => 100, 'height' => 63, 'x' => 565, 'y' => 400],
                'endWidth' => 100,
                'endHeight' => 63,
                'transformed' => true,
            ],
            'center mode' => [
                'params' => ['mode' => 'center', 'width' => 150, 'height' => 100],
                'endWidth' => 150,
                'endHeight' => 100,
                'transformed' => true,
            ],
            'center-x mode' => [
                'params' => ['mode' => 'center-x', 'y' => 10, 'width' => 50, 'height' => 40],
                'endWidth' => 50,
                'endHeight' => 40,
                'transformed' => true,
            ],
            'center-y mode' => [
                'params' => ['mode' => 'center-y', 'x' => 10, 'width' => 50, 'height' => 40],
                'endWidth' => 50,
                'endHeight' => 40,
                'transformed' => true,
            ],
        ];
    }

    /**
     * @return array<string,array{params:array<string,int>,originalWidth:int,originalHeight:int,width:int,height:int,x:int,y:int,shouldCrop:bool}>
     */
    public static function getImageParams(): array
    {
        return [
            'Do not perform work when cropping same sized images' => [
                'params' => ['width' => 123, 'height' => 234],
                'originalWidth' => 123,
                'originalHeight' => 234,
                'width' => 123,
                'height' => 234,
                'x' => 0,
                'y' => 0,
                'shouldCrop' => false,
            ],
            'Create new cropped image #1' => [
                'params' => ['width' => 100, 'height' => 200, 'y' => 10],
                'originalWidth' => 100,
                'originalHeight' => 400,
                'width' => 100,
                'height' => 200,
                'x' => 0,
                'y' => 10,
                'shouldCrop' => true,
            ],
            'Create new cropped image #2' => [
                'params' => ['width' => 123, 'height' => 234, 'x' => 10, 'y' => 20],
                'originalWidth' => 200,
                'originalHeight' => 260,
                'width' => 123,
                'height' => 234,
                'x' => 10,
                'y' => 20,
                'shouldCrop' => true,
            ],
        ];
    }

    /**
     * @return array<string,array{params:array<string,int>,originalWidth:int,originalHeight:int,errRegex?:string}>
     */
    public static function getInvalidImageParams(): array
    {
        return [
            'Dont throw if width/height are within bounds (no coords)' => [
                'params' => ['width' => 100, 'height' => 100],
                'originalWidth' => 200,
                'originalHeight' => 200,
            ],
            'Dont throw if coords are within bounds' => [
                'params' => ['width' => 100, 'height' => 100, 'x' => 100, 'y' => 100],
                'originalWidth' => 200,
                'originalHeight' => 200,
            ],
            'Throw if width is out of bounds'  => [
                'params' => ['width' => 300, 'height' => 100],
                'originalWidth' => 200,
                'originalHeight' => 200,
                'errRegex' => '#image width#i',
            ],
            'Throw if height is out of bounds' => [
                'params' => ['width' => 100, 'height' => 300],
                'originalWidth' => 200,
                'originalHeight' => 200,
                'errRegex' => '#image height#i',
            ],
            'Throw if X is out of bounds'  => [
                'params' => ['width' => 100, 'height' => 100, 'x' => 500],
                'originalWidth' => 200,
                'originalHeight' => 200,
                'errRegex' => '#image width#i',
            ],
            'Throw if Y is out of bounds'  => [
                'params' => ['width' => 100, 'height' => 100, 'y' => 500],
                'originalWidth' => 200,
                'originalHeight' => 200,
                'errRegex' => '#image height#i',
            ],
            'Throw if X + width is out of bounds'  => [
                'params' => ['width' => 100, 'height' => 100, 'x' => 105],
                'originalWidth' => 200,
                'originalHeight' => 200,
                'errRegex' => '#image width#i',
            ],
            'Throw if Y + height is out of bounds' => [
                'params' => ['width' => 100, 'height' => 100, 'y' => 105],
                'originalWidth' => 200,
                'originalHeight' => 200,
                'errRegex' => '#image height#i',
            ],
        ];
    }
}
