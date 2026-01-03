<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(User::class)]
class UserTest extends ResourceTests
{
    private User $resource;
    private EventInterface&MockObject $event;

    protected function getNewResource(): User
    {
        return new User();
    }

    public function setUp(): void
    {
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest'  => $this->createStub(Request::class),
            'getResponse' => $this->createStub(Response::class),
            'getDatabase' => $this->createStub(DatabaseInterface::class),
            'getStorage'  => $this->createStub(StorageInterface::class),
        ]);

        $this->resource = $this->getNewResource();
    }

    public function testSupportsHttpGet(): void
    {
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
