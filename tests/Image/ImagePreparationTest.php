<?php declare(strict_types=1);
namespace Imbo\Image;

use Imbo\Database\DatabaseInterface;
use Imbo\Image\ImagePreparation;
use Imbo\Exception\ImageException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\EventManager\EventInterface;
use Imbo\Image\Identifier\Generator\GeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Imagick;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\ImagePreparation
 */
class ImagePreparationTest extends TestCase {
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
        $this->request = $this->createMock(Request::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->headers = $this->createMock(ResponseHeaderBag::class);
        $this->response = $this->createMock(Response::class);
        $this->response->headers = $this->headers;
        $this->inputLoaderManager = $this->createMock(InputLoaderManager::class);
        $this->imagickLoader = function (string $mime, string $data) : Imagick {
            $imagick = new Imagick();
            $imagick->readImageBlob($data);

            return $imagick;
        };
        $this->outputConverterManager = $this->createMock(OutputConverterManager::class);
        $this->imageIdentifierGenerator = $this->createMock(GeneratorInterface::class);
        $this->config = ['imageIdentifierGenerator' => $this->imageIdentifierGenerator];
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getConfig' => $this->config,
            'getDatabase' => $this->database,
            'getInputLoaderManager' => $this->inputLoaderManager,
            'getOutputConverterManager' => $this->outputConverterManager,
        ]);

        $this->prepare = new ImagePreparation();
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsACorrectDefinition() : void {
        $class = get_class($this->prepare);
        $this->assertIsArray($class::getSubscribedEvents());
    }

    /**
     * @covers ::prepareImage
     */
    public function testThrowsExceptionWhenNoImageIsAttached() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('');

        $this->expectExceptionObject(new ImageException('No image attached', 400));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testThrowsExceptionWhenImageTypeIsNotSupported() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(file_get_contents(__FILE__));

        $this->inputLoaderManager
            ->expects($this->any())
            ->method('load')
            ->willReturn(null);

        $this->expectExceptionObject(new ImageException('Unsupported image type: text/x-php', 415));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers ::prepareImage
     */
    public function testThrowsExceptionWhenImageIsBroken() : void {
        $filePath = FIXTURES_DIR . '/broken-image.jpg';

        $this->inputLoaderManager
            ->expects($this->any())
            ->method('load')
            ->willReturnCallback($this->imagickLoader);

        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(file_get_contents($filePath));

        $this->expectExceptionObject(new ImageException('Invalid image', 415));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers ::prepareImage
     */
    public function testThrowsExceptionWhenImageIsSlightlyBroken() : void {
        $this->markTestSkipped('Test causes seg fault');

        $filePath = FIXTURES_DIR . '/slightly-broken-image.png';

        $this->inputLoaderManager
            ->expects($this->any())
            ->method('load')
            ->willReturnCallback($this->imagickLoader);

        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(file_get_contents($filePath));

        $this->expectExceptionObject(new ImageException('Invalid image', 415));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers ::prepareImage
     */
    public function testPopulatesRequestWhenImageIsValid() : void {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);

        $this->inputLoaderManager
            ->expects($this->any())
            ->method('load')
            ->willReturnCallback($this->imagickLoader);

        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($imageData);

        $this->request
            ->expects($this->once())
            ->method('setImage')
            ->with($this->isInstanceOf(Image::class));

        $this->prepare->prepareImage($this->event);
    }
}
