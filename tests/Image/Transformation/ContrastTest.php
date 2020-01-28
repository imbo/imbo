<?php
namespace Imbo\Image\Transformation;

use Imbo\Model\Image;
use Imbo\Exception\TransformationException;
use PHPUnit\Framework\TestCase;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Contrast
 */
class ContrastTest extends TestCase {
    public function getContrastParams() : array {
        return [
            'no params' => [
                [], true
            ],
            'positive contrast' => [
                ['alpha' => 2.5], true
            ],
            'zero contrast' => [
                ['alpha' => 0], false
            ],
            'negative contrast, specific beta' => [
                ['alpha' => -2, 'beta' => 0.75], true
            ],
        ];
    }

    /**
     * @dataProvider getContrastParams
     * @covers ::transform
     * @todo Rewrite test when we can get the call to Imagick::getQuantumRange() out of the
     *       Transformation class
     */
    public function testSetsTheCorrectContrast(array $params, bool $shouldTransform) {
        $image = $this->createMock(Image::class);

        $imagick = new Imagick();
        $imagick->newImage(16, 16, '#fff');

        if ($shouldTransform) {
            $image
                ->expects($this->once())
                ->method('hasBeenTransformed')
                ->with(true);
        } else {
            $image
                ->expects($this->never())
                ->method('hasBeenTransformed');
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
    public function testThrowsException() {
        $this->expectException(TransformationException::class);
        $this->expectExceptionCode(400);

        (new Contrast())
            ->setImagick(new Imagick())
            ->transform([]);
    }
}
