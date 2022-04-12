<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;
use Imbo\Model\Image as ImageModel;
use Imbo\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @coversDefaultClass Imbo\Resource\Image
 */
class ImageTest extends ResourceTests
{
    private $resource;
    private $request;
    private $response;
    private $database;
    private $storage;
    private $manager;
    private $event;

    protected function getNewResource(): Image
    {
        return new Image();
    }

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->manager = $this->createMock(EventManager::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getStorage' => $this->storage,
            'getManager' => $this->manager,
        ]);

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers ::deleteImage
     */
    public function testSupportsHttpDelete(): void
    {
        $this->manager
            ->method('trigger')
            ->withConsecutive(
                ['db.image.delete'],
                ['storage.image.delete'],
            );

        $this->request
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn('id');

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class));

        $this->resource->deleteImage($this->event);
    }

    /**
     * @covers ::getImage
     */
    public function testSupportsHttpGet(): void
    {
        $user = 'christer';
        $imageIdentifier = 'imageIdentifier';

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);

        $this->request
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->request
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn($imageIdentifier);

        $this->response->headers = $responseHeaders;

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ImageModel::class));

        $this->manager
            ->method('trigger')
            ->withConsecutive(
                ['db.image.load'],
                ['storage.image.load'],
            );

        $this->response
            ->expects($this->once())
            ->method('setMaxAge')
            ->with(31536000)
            ->willReturnSelf();

        $responseHeaders
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(function (array $headers): bool {
                return
                    array_key_exists('X-Imbo-OriginalMimeType', $headers)
                    && array_key_exists('X-Imbo-OriginalWidth', $headers)
                    && array_key_exists('X-Imbo-OriginalHeight', $headers)
                    && array_key_exists('X-Imbo-OriginalFileSize', $headers)
                    && array_key_exists('X-Imbo-OriginalExtension', $headers);
            }));

        $this->resource->getImage($this->event);
    }
}
