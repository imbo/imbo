<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Contrast
 */
class ContrastTest extends TestCase
{
    public function getContrastParams(): array
    {
        return [
            'no params' => [
                [], true,
            ],
            'positive contrast' => [
                ['alpha' => 2.5], true,
            ],
            'zero contrast' => [
                ['alpha' => 0], false,
            ],
            'negative contrast, specific beta' => [
                ['alpha' => -2, 'beta' => 0.75], true,
            ],
        ];
    }

    /**
     * @dataProvider getContrastParams
     * @covers ::transform
     * @todo Rewrite test when we can get the call to Imagick::getQuantumRange() out of the
     *       Transformation class
     */
    public function testSetsTheCorrectContrast(array $params, bool $shouldTransform)
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
     * @covers ::transform
     * @todo Rewrite test when we can get the call to Imagick::getQuantumRange() out of the
     *       Transformation class
     */
    public function testThrowsException()
    {
        $this->expectException(TransformationException::class);
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        (new Contrast())
            ->setImagick(new Imagick())
            ->transform([]);
    }
}
