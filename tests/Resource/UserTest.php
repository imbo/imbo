<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass Imbo\Resource\User
 */
class UserTest extends ResourceTests
{
    private User $resource;
    private Request&MockObject $request;
    private Response&MockObject $response;
    private DatabaseInterface&MockObject $database;
    private StorageInterface&MockObject $storage;
    private EventInterface&MockObject $event;

    protected function getNewResource(): User
    {
        return new User();
    }

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest'  => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getStorage'  => $this->storage,
        ]);

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers ::get
     */
    public function testSupportsHttpGet(): void
    {
        /** @var EventManager&MockObject */
        $manager = $this->createMock(EventManager::class);
        $manager
            ->expects($this->once())
            ->method('trigger')
            ->with('db.user.load');

        $this->event
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($manager);

        $this->resource->get($this->event);
    }
}
