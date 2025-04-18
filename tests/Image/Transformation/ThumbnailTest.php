<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Thumbnail::class)]
class ThumbnailTest extends TransformationTests
{
    protected function getTransformation(): Thumbnail
    {
        return new Thumbnail();
    }

    #[DataProvider('getThumbnailParams')]
    public function testCanTransformImage(array $params, int $width, int $height, int $diff = 0): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setWidth')
            ->with($this->callback(
                fn (int $setWidth): bool => $setWidth <= ($width + $diff) && $setWidth >= ($width - $diff),
            ))
            ->willReturn($image);

        $image
            ->expects($this->once())
            ->method('setHeight')
            ->with($this->callback(fn (int $setHeight): bool => $setHeight <= ($height + $diff) && $setHeight >= ($height - $diff)))
            ->willReturn($image);

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
     * @return array<string,array{params:array<string,string|int>,width:int,height:int,diff?:int}>
     */
    public static function getThumbnailParams(): array
    {
        return [
            'no params' => [
                'params' => [],
                'width'  => 50,
                'height' => 50,
            ],
            'only width' => [
                'params' => ['width' => 60],
                'width'  => 60,
                'height' => 50,
            ],
            'only height' => [
                'params' => ['height' => 60],
                'width'  => 50,
                'height' => 60,
            ],
            'only fit (inset)' => [
                'params' => ['fit' => 'inset'],
                'width'  => 50,
                'height' => 34,
                'diff'   => 1,
            ],
            'only fit (outbound)' => [
                'params' => ['fit' => 'outbound'],
                'width'  => 50,
                'height' => 50,
            ],
            'all params (inset)' => [
                'params' => ['width' => 123, 'height' => 456, 'fit' => 'inset'],
                'width'  => 123,
                'height' => 85,
                'diff'   => 1,
            ],
            'all params (outbound)' => [
                'params' => ['width' => 123, 'height' => 456, 'fit' => 'outbound'],
                'width'  => 123,
                'height' => 456,
            ],
        ];
    }
}
