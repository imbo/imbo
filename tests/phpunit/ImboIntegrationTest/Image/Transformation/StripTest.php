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

use Imbo\Image\Transformation\Strip,
    Imagick;

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
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getArgument')->with('image')->will($this->returnValue($image));

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

        // Need to create another Imagick instance here since the getImageProperties call above
        // seems to store the properties, so subsequent calls to that method will return the same
        // properties, even if a call to stripImage() has been made
        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/exif-logo.jpg'));

        $this->getTransformation()->setImagick($imagick)->transform($event);

        foreach ($imagick->getImageProperties() as $key => $value) {
            $this->assertStringStartsNotWith('exif', $key);
        }
    }
}
