<?php declare(strict_types=1);
namespace ImboUnitTest\EventListener;

use Imbo\EventListener\Imagick;

/**
 * @coversDefaultClass Imbo\EventListener\Imagick
 */
class ImagickTest extends ListenerTests {
    /**
     * @var Imagick
     */
    private $listener;

    private $request;
    private $response;
    private $event;
    private $transformationManager;
    private $inputLoaderManager;

    public function setUp() : void {
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->transformationManager = $this->createMock('Imbo\Image\TransformationManager');
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->inputLoaderManager = $this->createMock('Imbo\Image\InputLoaderManager');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getTransformationManager')->will($this->returnValue($this->transformationManager));
        $this->event->expects($this->any())->method('getInputLoaderManager')->will($this->returnValue($this->inputLoaderManager));

        $this->listener = new Imagick();
    }

    protected function getListener() : Imagick {
        return $this->listener;
    }


    /**
     * @covers ::readImageBlob
     * @covers ::setImagick
     */
    public function testFetchesImageFromRequest() : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image'));
        $image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/jpeg'));

        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $this->inputLoaderManager->expects($this->once())->method('load')->with('image/jpeg', 'image');

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('images.post'));
        $this->listener->readImageBlob($this->event);
    }

    /**
     * @covers ::readImageBlob
     * @covers ::setImagick
     */
    public function testFetchesImageFromResponse() : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image'));
        $image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/jpeg'));

        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));

        $this->inputLoaderManager->expects($this->once())->method('load')->with('image/jpeg', 'image');

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('storage.image.load'));

        $this->listener->readImageBlob($this->event);
    }

    public function hasImageBeenTransformed() : array {
        return [
            'has been transformed' => [true],
            'has not been transformed' => [false],
        ];
    }

    /**
     * @covers ::readImageBlob
     * @covers ::setImagick
     * @dataProvider hasImageBeenTransformed
     */
    public function testUpdatesModelBeforeStoring(bool $hasBeenTransformed) : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->will($this->returnValue($hasBeenTransformed));

        $imagick = $this->createMock('Imagick');

        if ($hasBeenTransformed) {
            $imagick->expects($this->once())->method('getImageBlob')->will($this->returnValue('image'));
            $image->expects($this->once())->method('setBlob')->with('image');
        } else {
            $imagick->expects($this->never())->method('getImageBlob');
            $image->expects($this->never())->method('setBlob');
        }

        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $this->listener->setImagick($imagick)
                       ->updateModelBeforeStoring($this->event);
    }

    /**
     * @covers ::readImageBlob
     * @covers ::setImagick
     * @dataProvider hasImageBeenTransformed
     */
    public function testUpdatesModelBeforeSendingResponse(bool $hasBeenTransformed) : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->will($this->returnValue($hasBeenTransformed));

        $imagick = $this->createMock('Imagick');

        if ($hasBeenTransformed) {
            $imagick->expects($this->once())->method('getImageBlob')->will($this->returnValue('image'));
            $image->expects($this->once())->method('setBlob')->with('image');
        } else {
            $imagick->expects($this->never())->method('getImageBlob');
            $image->expects($this->never())->method('setBlob');
        }

        $this->event->expects($this->once())->method('getArgument')->with('image')->will($this->returnValue($image));

        $this->listener->setImagick($imagick)
                       ->updateModel($this->event);
    }
}
