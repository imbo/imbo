<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Border;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Border
 */
class BorderTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Border();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getBorderParams() {
        return [
            'inline border' => [665, 463, 3, 4, 'inset'],
            'outbound border' => [671, 471, 3, 4, 'outbound'],
        ];
    }

    /**
     * @dataProvider getBorderParams
     */
    public function testTransformationSupportsDifferentModes($expectedWidth, $expectedHeight, $borderWidth, $borderHeight, $borderMode) {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setWidth')->with($expectedWidth)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($expectedHeight)->will($this->returnValue($image));
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $blob = file_get_contents(FIXTURES_DIR . '/image.png');

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()->setImagick($imagick)->setImage($image)->transform([
            'color' => 'white',
            'width' => $borderWidth,
            'height' => $borderHeight,
            'mode' => $borderMode,
        ]);
    }
}
