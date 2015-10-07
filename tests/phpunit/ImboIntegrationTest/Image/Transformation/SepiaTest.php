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

use Imbo\Image\Transformation\Sepia,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\Sepia
 * @group integration
 * @group transformations
 */
class SepiaTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Sepia();
    }

    /**
     * @covers Imbo\Image\Transformation\Sepia::transform
     */
    public function testCanTransformImageWithoutParams() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue([]));
        $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImagick($imagick)->transform($event);
    }

    /**
     * @covers Imbo\Image\Transformation\Sepia::transform
     */
    public function testCanTransformImageWithParams() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue(['threshold' => 10]));
        $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImagick($imagick)->transform($event);
    }
}
