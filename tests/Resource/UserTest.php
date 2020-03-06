<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;

/**
 * @coversDefaultClass Imbo\Resource\User
 */
class UserTest extends ResourceTests {
    private $resource;
    private $request;
    private $response;
    private $database;
    private $storage;
    private $event;

    protected function getNewResource() : User {
        return new User();
    }

    public function setUp() : void {
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
    public function testSupportsHttpGet() : void {
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
