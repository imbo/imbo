<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Modulate;
use Imagick;

/**
 * @covers Imbo\Image\Transformation\Modulate
 * @group integration
 * @group transformations
 */
class ModulateTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Modulate();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getModulateParams() {
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
    public function testCanModulateImages(array $params) {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform($params);
    }
}
