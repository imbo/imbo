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

use Imbo\Image\Transformation\Strip;
use Imagick;

/**
 * @covers Imbo\Image\Transformation\Strip
 * @group integration
 * @group transformations
 */
class StripTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Strip();
    }

    /**
     * @covers Imbo\Image\Transformation\Strip::transform
     */
    public function testStripMetadata() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/exif-logo.jpg'));

        $exifExists = false;

        foreach ($imagick->getImageProperties() as $key => $value) {
            if (substr($key, 0, 5) === 'exif:') {
                $exifExists = true;
                break;
            }
        }

        if (!$exifExists) {
            $this->fail('Image is missing EXIF data');
        }

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);

        foreach ($imagick->getImageProperties() as $key => $value) {
            $this->assertStringStartsNotWith('exif', $key);
        }
    }
}
