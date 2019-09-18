<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Modulate;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Modulate
 */
class ModulateTest extends TransformationTests {
    protected function getTransformation() : Modulate {
        return new Modulate();
    }

    public function getModulateParams() : array {
        return [
            'no params' => [
                [],
            ],
            'some params' => [
                ['b' => 10, 's' => 10],
            ],
            'all params' => [
                ['b' => 1, 's' => 2, 'h' => 3],
            ],
        ];
    }

    /**
     * @dataProvider getModulateParams
     */
    public function testCanModulateImages(array $params) : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform($params);
    }
}
