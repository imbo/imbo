<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\DuplicateImageIdentifierException;
use Imbo\Exception\ImageException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\Identifier\Generator\GeneratorInterface;
use Imbo\Model\ArrayModel;
use Imbo\Model\Image;
use Imbo\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @coversDefaultClass Imbo\Resource\Images
 */
class ImagesTest extends ResourceTests
{
    private $resource;
    private $request;
    private $response;
    private $database;
    private $storage;
    private $manager;
    private $event;
    private $imageIdentifierGenerator;
    private $config;

    protected function getNewResource(): Images
    {
        return new Images();
    }

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->manager = $this->createMock(EventManager::class);
        $this->imageIdentifierGenerator = $this->createMock(GeneratorInterface::class);
        $this->config = ['imageIdentifierGenerator' => $this->imageIdentifierGenerator];

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getStorage' => $this->storage,
            'getManager' => $this->manager,
            'getConfig' => $this->config,
        ]);

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers ::addImage
     */
    public function testSupportsHttpPost(): void
    {
        $this->imageIdentifierGenerator
            ->expects($this->any())
            ->method('isDeterministic')
            ->willReturn(false);
        $this->imageIdentifierGenerator
            ->expects($this->any())
            ->method('generate')
            ->willReturn('id');

        $manager = $this->manager;
        $manager
            ->method('trigger')
            ->willReturnCallback(
                static function (string $event, array $params = []) use ($manager) {
                    static $i = 0;
                    return match ([$i++, $event, $params]) {
                        [0, 'db.image.insert', ['updateIfDuplicate' => false]],
                        [1, 'storage.image.insert', []] => $manager,
                    };
                },
            );

        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn('id');

        $this->request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class));

        $this->resource->addImage($this->event);
    }

    /**
     * @covers ::addImage
     */
    public function testThrowsExceptionWhenItFailsToGenerateUniqueImageIdentifier(): void
    {
        $this->manager
            ->expects($this->any())
            ->method('trigger')
            ->with('db.image.insert', ['updateIfDuplicate' => false])
            ->willThrowException(new DuplicateImageIdentifierException());

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->once())
            ->method('set')
            ->with('Retry-After', 1);

        $this->response->headers = $headers;

        $image = $this->createMock(Image::class);

        $this->imageIdentifierGenerator
            ->expects($this->any())
            ->method('isDeterministic')
            ->willReturn(false);
        $this->imageIdentifierGenerator
            ->expects($this->any())
            ->method('generate')
            ->willReturn('foo');

        $this->request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        $this->expectExceptionObject(new ImageException('Failed to generate unique image identifier', Response::HTTP_SERVICE_UNAVAILABLE));
        $this->resource->addImage($this->event);
    }

    /**
     * @covers ::getImages
     */
    public function testSupportsHttpGet(): void
    {
        $this->manager
            ->expects($this->once())
            ->method('trigger')
            ->with('db.images.load');
        $this->resource->getImages($this->event);
    }

    /**
     * @covers ::addImage
     */
    public function testAddImageWithCallableImageIdentifierGenerator(): void
    {
        $manager = $this->manager;
        $manager
            ->method('trigger')
            ->willReturnCallback(
                static function (string $event, array $params = []) use ($manager) {
                    static $i = 0;
                    return match ([$i++, $event, $params]) {
                        [0, 'db.image.insert', ['updateIfDuplicate' => true]] => throw new DuplicateImageIdentifierException(),
                        [1, 'db.image.insert', ['updateIfDuplicate' => true]],
                        [2, 'db.image.insert', ['updateIfDuplicate' => true]],
                        [3, 'storage.image.insert', []] => $manager,
                    };
                },
            );

        $image = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => 'some id',
        ]);
        $image
            ->expects($this->exactly(2))
            ->method('setImageIdentifier')
            ->with('some id');

        $this->request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class));

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getStorage' => $this->storage,
            'getManager' => $this->manager,
            'getConfig' => ['imageIdentifierGenerator' => new IdGenerator()],
        ]);

        $this->resource->addImage($event);
    }
}

class IdGenerator
{
    public function generate(Image $image): string
    {
        return 'some id';
    }

    public function isDeterministic(): bool
    {
        return true;
    }
}
