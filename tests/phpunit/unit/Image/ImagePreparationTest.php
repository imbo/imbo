<?php declare(strict_types=1);
namespace ImboUnitTest\Image;

use Imbo\Image\ImagePreparation;
use Imbo\Exception\ImageException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\ImagePreparation
 */
class ImagePreparationTest extends TestCase {
    /**
     * @var ImagePreparation
     */
    private $prepare;

    private $request;
    private $response;
    private $event;
    private $config;
    private $database;
    private $headers;
    private $imageIdentifierGenerator;
    private $inputLoaderManager;
    private $outputConverterManager;
    private $imagickLoader;

    /**
     * Set up the image preparation instance
     */
    public function setUp() : void {
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->database = $this->createMock('Imbo\Database\DatabaseInterface');
        $this->headers = $this->createMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $this->inputLoaderManager = $this->createMock('Imbo\Image\InputLoaderManager');
        $this->imagickLoader = function ($mime, $data) {
            $imagick = new \Imagick();
            $imagick->readImageBlob($data);
            return $imagick;
        };
        $this->outputConverterManager = $this->createMock('Imbo\Image\OutputConverterManager');
        $this->response->headers = $this->headers;
        $this->imageIdentifierGenerator = $this->createMock('Imbo\Image\Identifier\Generator\GeneratorInterface');
        $this->config = ['imageIdentifierGenerator' => $this->imageIdentifierGenerator];
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getConfig')->will($this->returnValue($this->config));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getInputLoaderManager')->will($this->returnValue($this->inputLoaderManager));
        $this->event->expects($this->any())->method('getOutputConverterManager')->will($this->returnValue($this->outputConverterManager));

        $this->prepare = new ImagePreparation();
    }

    /**
     * @covers Imbo\Image\ImagePreparation::getSubscribedEvents
     */
    public function testReturnsACorrectDefinition() : void {
        $class = get_class($this->prepare);
        $this->assertIsArray($class::getSubscribedEvents());
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testThrowsExceptionWhenNoImageIsAttached() : void {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(''));
        $this->expectExceptionObject(new ImageException('No image attached', 400));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testThrowsExceptionWhenImageTypeIsNotSupported() : void {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents(__FILE__)));
        $this->inputLoaderManager->expects($this->any())->method('load')->will($this->returnValue(null));
        $this->expectExceptionObject(new ImageException('Unsupported image type: text/x-php', 415));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testThrowsExceptionWhenImageIsBroken() : void {
        $filePath = FIXTURES_DIR . '/broken-image.jpg';

        $this->inputLoaderManager->expects($this->any())->method('load')->will($this->returnCallback($this->imagickLoader));
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents($filePath)));
        $this->expectExceptionObject(new ImageException('Invalid image', 415));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testThrowsExceptionWhenImageIsSlightlyBroken() : void {
        $this->markTestSkipped('Test causes seg fault');

        $filePath = FIXTURES_DIR . '/slightly-broken-image.png';

        $this->inputLoaderManager->expects($this->any())->method('load')->will($this->returnCallback($this->imagickLoader));
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents($filePath)));
        $this->expectExceptionObject(new ImageException('Invalid image', 415));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testPopulatesRequestWhenImageIsValid() : void {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);

        $this->inputLoaderManager->expects($this->any())->method('load')->will($this->returnCallback($this->imagickLoader));

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue($imageData));
        $this->request->expects($this->once())->method('setImage')->with($this->isInstanceOf('Imbo\Model\Image'));
        $this->prepare->prepareImage($this->event);
    }
}
