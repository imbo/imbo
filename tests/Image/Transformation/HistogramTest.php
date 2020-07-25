<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Model\Image;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Histogram
 */
class HistogramTest extends TransformationTests {
    protected function getTransformation() : Histogram {
        return new Histogram();
    }

    public function getHistogramParameters() : array {
        return [
            [1, 256],
            [2, 512],
            [4, 1024],
            [8, 2048],
        ];
    }

    /**
     * @dataProvider getHistogramParameters
     * @covers ::transform
     */
    public function testTransformWithDifferentParameters(int $scale, int $resultingWidth) : void {
        $blob = file_get_contents(FIXTURES_DIR . '/512x512.png');

        $image = $this->createConfiguredMock(Image::class, [
            'getBlob' => $blob,
            'getWidth' => 512,
            'getHeight' => 512,
            'getExtension' => 'png',
        ]);

        $image
            ->expects($this->once())
            ->method('setWidth')
            ->with($resultingWidth)
            ->willReturn($image);

        $image
            ->expects($this->once())
            ->method('setHeight')
            ->willReturn($image);

        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([
                'scale' => $scale,
            ]);
    }
}
