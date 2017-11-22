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

use Imbo\Image\Transformation\Convert;
use Imagick;

/**
 * @covers Imbo\Image\Transformation\Convert
 * @group integration
 * @group transformations
 */
class ConvertTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Convert();
    }

    public function testCanConvertAnImage() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getExtension')->will($this->returnValue('png'));
        $image->expects($this->once())->method('setMimeType')->with('image/gif')->will($this->returnValue($image));
        $image->expects($this->once())->method('setExtension')->with('gif')->will($this->returnValue($image));
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $event = $this->createMock('Imbo\EventManager\Event');
        $outputConverterManager = $this->createMock('Imbo\Image\OutputConverterManager');
        $outputConverterManager->expects($this->any())->method('getMimetypeFromExtension')->with('gif')->will($this->returnValue('image/gif'));
        $event->expects($this->any())->method('getOutputConverterManager')->will($this->returnValue($outputConverterManager));

        $this->getTransformation()
            ->setEvent($event)
            ->setImage($image)
            ->setImagick($imagick)
            ->transform(['type' => 'gif']);
    }
}
