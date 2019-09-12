<?php
namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Contrast;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Contrast
 */
class ContrastTest extends TestCase {
    /**
     * @var Contrast
     */
    private $transformation;

    /**
     * Set up the transformation instance
     */
    public function setUp() : void {
        $this->transformation = new Contrast();
    }

    public function getContrastParams() {
        $imagick = new \Imagick();
        if (is_callable([$imagick, 'getQuantumRange'])) {
            $quantumRange = $imagick->getQuantumRange();
        } else {
            $quantumRange = \Imagick::getQuantumRange();
        }

        $quantumRange = $quantumRange['quantumRangeLong'];

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
     */
    public function testSetsTheCorrectContrast(array $params, $shouldTransform) {
        $image = $this->createMock('Imbo\Model\Image');

        $imagick = new \Imagick();
        $imagick->newImage(16, 16, '#fff');

        if ($shouldTransform) {
            $image->expects($this->once())->method('hasBeenTransformed')->with(true);
        } else {
            $image->expects($this->never())->method('hasBeenTransformed');
        }

        $this->transformation->setImage($image);
        $this->transformation->setImagick($imagick);
        $this->transformation->transform($params);
    }
}
