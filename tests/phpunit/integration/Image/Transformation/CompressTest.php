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

use Imbo\Image\Transformation\Compress,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\Compress
 * @group integration
 * @group transformations
 */
class CompressTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Compress();
    }

    public function testCanTransformTheImage() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue('image/jpeg'));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue(['level' => 50]));
        $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $transformation = $this->getTransformation();
        $transformation->setImagick($imagick);
        $transformation->transform($event); // Set the correct level parameter
        $transformation->compress($event); // Perform the actual compression
    }
}
