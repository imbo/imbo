<?php declare(strict_types=1);
namespace Imbo\Image;

use Closure;
use Imagick;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\ImageException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\Identifier\Generator\GeneratorInterface;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(ImagePreparation::class)]
class ImagePreparationTest extends TestCase
{
    private ImagePreparation $prepare;
    private Request&MockObject $request;
    private EventInterface $event;
    private array $config;
    private InputLoaderManager $inputLoaderManager;
    private Closure $imagickLoader;

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $response = $this->createStub(Response::class);
        $response->headers = $this->createStub(ResponseHeaderBag::class);
        $this->inputLoaderManager = $this->createStub(InputLoaderManager::class);
        $this->imagickLoader = function (string $mime, string $data): Imagick {
            $imagick = new Imagick();
            $imagick->readImageBlob($data);
            return $imagick;
        };
        $this->config = ['imageIdentifierGenerator' => $this->createStub(GeneratorInterface::class)];
        $this->event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $response,
            'getConfig' => $this->config,
            'getDatabase' => $this->createStub(DatabaseInterface::class),
            'getInputLoaderManager' => $this->inputLoaderManager,
            'getOutputConverterManager' => $this->createStub(OutputConverterManager::class),
        ]);

        $this->prepare = new ImagePreparation();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testReturnsACorrectDefinition(): void
    {
        $class = get_class($this->prepare);
        $this->assertIsArray($class::getSubscribedEvents());
    }

    public function testThrowsExceptionWhenNoImageIsAttached(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('');

        $this->expectExceptionObject(new ImageException('No image attached', Response::HTTP_BAD_REQUEST));
        $this->prepare->prepareImage($this->event);
    }

    public function testThrowsExceptionWhenImageTypeIsNotSupported(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(file_get_contents(__FILE__));

        $this->inputLoaderManager
            ->method('load')
            ->willReturn(null);

        $this->expectExceptionObject(new ImageException('Unsupported image type: text/x-php', Response::HTTP_UNSUPPORTED_MEDIA_TYPE));
        $this->prepare->prepareImage($this->event);
    }

    public function testThrowsExceptionWhenImageIsBroken(): void
    {
        $filePath = FIXTURES_DIR . '/broken-image.jpg';

        $this->inputLoaderManager
            ->method('load')
            ->willReturnCallback($this->imagickLoader);

        $this->inputLoaderManager
            ->method('getExtensionFromMimetype')
            ->with('image/jpeg')
            ->willReturn('jpg');

        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(file_get_contents($filePath));

        $this->expectExceptionObject(new ImageException('Invalid image', Response::HTTP_UNSUPPORTED_MEDIA_TYPE));
        $this->prepare->prepareImage($this->event);
    }

    public function testThrowsExceptionWhenImageIsSlightlyBroken(): void
    {
        $filePath = FIXTURES_DIR . '/slightly-broken-image.png';

        $this->inputLoaderManager
            ->method('load')
            ->willReturnCallback($this->imagickLoader);

        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(file_get_contents($filePath));

        $this->expectExceptionObject(new ImageException('Invalid image', Response::HTTP_UNSUPPORTED_MEDIA_TYPE));
        $this->prepare->prepareImage($this->event);
    }


    public function testPopulatesRequestWhenImageIsValid(): void
    {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);

        $this->inputLoaderManager
            ->method('load')
            ->willReturnCallback($this->imagickLoader);

        $this->inputLoaderManager
            ->method('getExtensionFromMimetype')
            ->with('image/png')
            ->willReturn('png');

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
