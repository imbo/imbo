<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Resize
 */
class ResizeTest extends TransformationTests
{
    protected function getTransformation(): Resize
    {
        return new Resize();
    }

    /**
     * @dataProvider getResizeParams
     * @covers ::transform
     */
    public function testCanTransformImage(array $params, bool $transformation, int $resizedWidth = null, int $resizedHeight = null): void
    {
        /** @var Image&MockObject */
        $image = $this->createConfiguredMock(Image::class, [
            'getWidth' => 665,
            'getHeight' => 463,
        ]);

        if ($transformation) {
            $image
                ->expects($this->once())
                ->method('setWidth')
                ->with($resizedWidth)
                ->willReturn($image);

            $image
                ->expects($this->once())
                ->method('setHeight')
                ->with($resizedHeight)
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
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform($params);
    }

    /**
     * @return array<string,array{params:array<string,int>,transformation:bool,resizedWidth?:int,resizedHeight?:int}>
     */
    public static function getResizeParams(): array
    {
        return [
            'only width' => [
                'params'         => ['width' => 100],
                'transformation' => true,
                'resizedWidth'   => 100,
                'resizedHeight'  => 70,
            ],
            'only height' => [
                'params'         => ['height' => 100],
                'transformation' => true,
                'resizedWidth'   => 144,
                'resizedHeight'  => 100,
            ],
            'width and height' => [
                'params'         => ['width' => 100, 'height' => 200],
                'transformation' => true,
                'resizedWidth'   => 100,
                'resizedHeight'  => 200,
            ],
            'params match image size' => [
                'params'         => ['width' => 665, 'height' => 463],
                'transformation' => false,
            ],
        ];
    }
}
