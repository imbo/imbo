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

use Imbo\Image\Transformation\Modulate,
    Imagick;

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
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue($params));
        $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImagick($imagick)->transform($event);
    }
}
