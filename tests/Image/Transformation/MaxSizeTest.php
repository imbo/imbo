<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(MaxSize::class)]
class MaxSizeTest extends TransformationTests
{
    protected function getTransformation(): MaxSize
    {
        return new MaxSize();
    }

    #[DataProvider('getMaxSizeParams')]
    public function testCanTransformImages(string $file, array $params, int $width, int $height, ?int $transformedWidth, ?int $transformedHeight, ?bool $transformation = true): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getWidth' => $width,
            'getHeight' => $height,
        ]);

        if ($transformation) {
            $image
                ->expects($this->once())
                ->method('setWidth')
                ->with($transformedWidth)
                ->willReturn($image);

            $image
                ->expects($this->once())
                ->method('setHeight')
                ->with($transformedHeight)
                ->willReturn($image);

            $image
                ->expects($this->once())
                ->method('setHasBeenTransformed')
                ->with(true)
                ->willReturn($image);
        } else {
            $image
                ->expects($this->never())
                ->method('setWidth');

            $image
                ->expects($this->never())
                ->method('setHeight');

            $image
                ->expects($this->never())
                ->method('setHasBeenTransformed');
        }

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents($file));

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform($params);
    }

    /**
     * @return array<string,array{file:string,params:array<string,int>,width:int,height:int,transformedWidth:?int,transformedHeight:?int,transformation?:bool}>
     */
    public static function getMaxSizeParams(): array
    {
        return [
            'landscape image with only width in params' => [
                'file' => FIXTURES_DIR . '/image.png',
                'params' => ['width' => 200],
                'width' => 665,
                'height' => 463,
                'transformedWidth' => 200,
                'transformedHeight' => 139,
            ],
            'landscape image with only height in params' => [
                'file' => FIXTURES_DIR . '/image.png',
                'params' => ['height' => 100],
                'width' => 665,
                'height' => 463,
                'transformedWidth' => 144,
                'transformedHeight' => 100,
            ],
            'landscape image both width and height in params' => [
                'file' => FIXTURES_DIR . '/image.png',
                'params' => ['width' => 100, 'height' => 100],
                'width' => 665,
                'height' => 463,
                'transformedWidth' => 100,
                'transformedHeight' => 70,
            ],
            'landscape image smaller than width and height params' => [
                'file' => FIXTURES_DIR . '/image.png',
                'params' => ['width' => 1000, 'height' => 1000],
                'width' => 665,
                'height' => 463,
                'transformedWidth' => null,
                'transformedHeight' => null,
                'transformation' => false,
            ],
            'portrait image with only width in params' => [
                'file' => FIXTURES_DIR . '/tall-image.png',
                'params' => ['width' => 200],
                'width' => 463,
                'height' => 665,
                'transformedWidth' => 200,
                'transformedHeight' => 287,
            ],
            'portrait image with only height in params' => [
                'file' => FIXTURES_DIR . '/tall-image.png',
                'params' => ['height' => 100],
                'width' => 463,
                'height' => 665,
                'transformedWidth' => 70,
                'transformedHeight' => 100,
            ],
            'portrait image both width and height in params' => [
                'file' => FIXTURES_DIR . '/tall-image.png',
                'params' => ['width' => 100, 'height' => 100],
                'width' => 463,
                'height' => 665,
                'transformedWidth' => 70,
                'transformedHeight' => 100,
            ],
            'portrait image smaller than width and height params' => [
                'file' => FIXTURES_DIR . '/tall-image.png',
                'params' => ['width' => 1000, 'height' => 1000],
                'width' => 463,
                'height' => 665,
                'transformedWidth' => null,
                'transformedHeight' => null,
                'transformation' => false,
            ],
        ];
    }
}
