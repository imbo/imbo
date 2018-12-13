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
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setOutputQualityCompression')->with(50);
        $event = $this->createMock('Imbo\EventManager\Event');

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $transformation = $this->getTransformation();
        $transformation
            ->setImagick($imagick)
            ->setImage($image)
            ->setEvent($event)
            ->transform(['level' => 50]);
    }
}
