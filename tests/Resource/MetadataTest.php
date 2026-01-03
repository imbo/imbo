<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;
use Imbo\Model\ModelInterface;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(Metadata::class)]
class MetadataTest extends ResourceTests
{
    private Metadata $resource;
    private Request&MockObject $request;
    private Response&MockObject $response;
    private DatabaseInterface&MockObject $database;
    private EventManager&MockObject $manager;
    private EventInterface $event;

    protected function getNewResource(): Metadata
    {
        return new Metadata();
    }

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->manager = $this->createMock(EventManager::class);
        $this->event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getStorage' => $this->createStub(StorageInterface::class),
            'getManager' => $this->manager,
        ]);

        $this->resource = $this->getNewResource();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsHttpDelete(): void
    {
        $this->manager
            ->expects($this->once())
            ->method('trigger')
            ->with('db.metadata.delete');
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class));

        $this->resource->delete($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsHttpPut(): void
    {
        $metadata = ['foo' => 'bar'];
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"foo":"bar"}');
        $manager = $this->manager;
        $this->manager
            ->method('trigger')
            ->willReturnCallback(
                static function (string $event, array $params = []) use ($metadata, $manager) {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $event, $params]) {
                        [0, 'db.metadata.delete', []],
                        [1, 'db.metadata.update', ['metadata' => $metadata]] => $manager,
                    };
                },
            );
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class));

        $this->resource->put($this->event);
    }

    public function testSupportsHttpPost(): void
    {
        $metadata = ['foo' => 'bar'];
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"foo":"bar"}');
        $this->manager
            ->expects($this->once())
            ->method('trigger')
            ->with('db.metadata.update', ['metadata' => $metadata]);
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ModelInterface::class));
        $this->database
            ->expects($this->once())
            ->method('getMetadata')
            ->with('user', 'id')
            ->willReturn(['foo' => 'bar']);
        $this->request
            ->expects($this->once())
            ->method('getUser')
            ->willReturn('user');
        $this->request
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn('id');

        $this->resource->post($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsHttpGet(): void
    {
        $this->manager
            ->expects($this->once())
            ->method('trigger')
            ->with('db.metadata.load');
        $this->resource->get($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenValidatingMissingJsonData(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(null);
        $this->expectExceptionObject(new InvalidArgumentException('Missing JSON data', Response::HTTP_BAD_REQUEST));
        $this->resource->validateMetadata($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenValidatingInvalidJsonData(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('some string');
        $this->expectExceptionObject(new InvalidArgumentException('Invalid JSON data', Response::HTTP_BAD_REQUEST));
        $this->resource->validateMetadata($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testAllowsValidJsonData(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"foo":"bar"}');
        $this->resource->validateMetadata($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionOnInvalidKeys(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"foo.bar":"bar"}');
        $this->expectExceptionObject(new InvalidArgumentException('Invalid metadata. Dot characters (\'.\') are not allowed in metadata keys', Response::HTTP_BAD_REQUEST));
        $this->resource->validateMetadata($this->event);
    }
}
