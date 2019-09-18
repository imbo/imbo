<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Convert;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Convert
 */
class ConvertTest extends TransformationTests {
    protected function getTransformation() : Convert {
        return new Convert();
    }

    public function testCanConvertAnImage() : void {
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
