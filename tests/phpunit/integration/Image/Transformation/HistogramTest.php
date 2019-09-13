<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Histogram;
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
     */
    public function testTransformWithDifferentParameters(int $scale, int $resultingWidth) : void {
        $blob = file_get_contents(FIXTURES_DIR . '/512x512.png');

        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue($blob));
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(512));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(512));
        $image->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $image->expects($this->once())->method('setWidth')->with($resultingWidth)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->will($this->returnValue($image));
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([
            'scale' => $scale,
        ]);
    }
}
