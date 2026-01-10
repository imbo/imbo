<?php declare(strict_types=1);

namespace Imbo\EventListener;

use DateTime;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\StorageException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(StorageOperations::class)]
class StorageOperationsTest extends ListenerTests
{
    private StorageOperations $listener;
    private EventInterface&MockObject $event;
    private Request&MockObject $request;
    private Response&MockObject $response;
    private string $user = 'user';
    private string $imageIdentifier = 'id';
    private StorageInterface&MockObject $storage;

    protected function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->request = $this->createConfiguredMock(Request::class, [
            'getUser' => $this->user,
            'getImageIdentifier' => $this->imageIdentifier,
        ]);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getStorage' => $this->storage,
        ]);

        $this->listener = new StorageOperations();
    }

    protected function getListener(): StorageOperations
    {
        return $this->listener;
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanDeleteAnImage(): void
    {
        $this->storage
            ->expects($this->once())
            ->method('delete')
            ->with($this->user, $this->imageIdentifier);

        $this->listener->deleteImage($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanLoadImage(): void
    {
        $date = new DateTime();
        $this->storage
            ->expects($this->once())
            ->method('getImage')
            ->with($this->user, $this->imageIdentifier)
            ->willReturn('image data');
        $this->storage
            ->expects($this->once())
            ->method('getLastModified')
            ->with($this->user, $this->imageIdentifier)
            ->willReturn($date);

        $this->response
            ->expects($this->once())
            ->method('setLastModified')
            ->with($date)
            ->willReturnSelf();

        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setBlob')
            ->with('image data');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $eventManager = $this->createMock(EventManager::class);
        $eventManager
            ->expects($this->once())
            ->method('trigger')
            ->with('image.loaded');

        $this->event
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($eventManager);

        $this->listener->loadImage($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testExceptionIfLoadImageFails(): void
    {
        $this->storage
            ->expects($this->once())
            ->method('getImage')
            ->with($this->user, $this->imageIdentifier)
            ->willReturn(null);
        $this->expectExceptionObject(new StorageException('Failed reading file from storage backend', Response::HTTP_SERVICE_UNAVAILABLE));

        $this->listener->loadImage($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanInsertImage(): void
    {
        $image = $this->createConfiguredStub(Image::class, [
            'getBlob' => 'image data',
            'getImageIdentifier' => 'imageId',
        ]);

        $this->request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(Response::HTTP_CREATED);

        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->user, 'imageId', 'image data');
        $this->storage
            ->expects($this->once())
            ->method('imageExists')
            ->with($this->user, 'imageId')
            ->willReturn(false);

        $this->listener->insertImage($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanInsertImageThatAlreadyExists(): void
    {
        $image = $this->createConfiguredStub(Image::class, [
            'getBlob' => 'image data',
            'getImageIdentifier' => 'imageId',
        ]);

        $this->request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(Response::HTTP_OK);

        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->user, 'imageId', 'image data');
        $this->storage
            ->expects($this->once())
            ->method('imageExists')
            ->with($this->user, 'imageId')
            ->willReturn(true);

        $this->listener->insertImage($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWillDeleteImageFromDatabaseAndThrowExceptionWhenStoringFails(): void
    {
        $image = $this->createConfiguredStub(Image::class, [
            'getBlob' => 'image data',
            'getImageIdentifier' => 'imageId',
        ]);

        $this->request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->user, 'imageId', 'image data')
            ->willThrowException(
                new StorageException('Could not store image', Response::HTTP_INTERNAL_SERVER_ERROR),
            );

        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->expects($this->once())
            ->method('deleteImage')
            ->with($this->user, 'imageId');

        $this->event
            ->expects($this->once())
            ->method('getDatabase')
            ->willReturn($database);

        $this->expectExceptionObject(new StorageException('Could not store image', Response::HTTP_INTERNAL_SERVER_ERROR));

        $this->listener->insertImage($this->event);
    }
}
