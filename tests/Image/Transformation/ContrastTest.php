<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Contrast::class)]
class ContrastTest extends TestCase
{
    /**
     * @todo Rewrite test when we can get the call to Imagick::getQuantumRange() out of the
     *       Transformation class
     */
    #[DataProvider('getContrastParams')]
    public function testSetsTheCorrectContrast(array $params, bool $shouldTransform): void
    {
        $image = $this->createMock(Image::class);

        $imagick = new Imagick();
        $imagick->newImage(16, 16, '#fff');

        if ($shouldTransform) {
            $image
                ->expects($this->once())
                ->method('setHasBeenTransformed')
                ->with(true);
        } else {
            $image
                ->expects($this->never())
                ->method('setHasBeenTransformed');
        }

        (new Contrast())
            ->setImage($image)
            ->setImagick($imagick)
            ->transform($params);
    }

    /**
     * @todo Rewrite test when we can get the call to Imagick::getQuantumRange() out of the
     *       Transformation class
     */
    public function testThrowsException(): void
    {
        $this->expectException(TransformationException::class);
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        (new Contrast())
            ->setImagick(new Imagick())
            ->transform([]);
    }

    /**
     * @return array<string,array{params:array<string,double>,shouldTransform:bool}>
     */
    public static function getContrastParams(): array
    {
        return [
            'no params' => [
                'params' => [],
                'shouldTransform' => true,
            ],
            'positive contrast' => [
                'params' => ['alpha' => 2.5],
                'shouldTransform' => true,
            ],
            'zero contrast' => [
                'params' => ['alpha' => 0],
                'shouldTransform' => false,
            ],
            'negative contrast, specific beta' => [
                'params' => ['alpha' => -2, 'beta' => 0.75],
                'shouldTransform' => true,
            ],
        ];
    }
}
