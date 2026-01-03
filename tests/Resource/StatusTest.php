<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Http\Response\Response;
use Imbo\Model\Status as StatusModel;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(Status::class)]
class StatusTest extends ResourceTests
{
    private Status $resource;
    private Response&MockObject $response;
    private DatabaseInterface&MockObject $database;
    private StorageInterface&MockObject $storage;
    private EventInterface $event;

    protected function getNewResource(): Status
    {
        return new Status();
    }

    public function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->event = $this->createConfiguredStub(EventInterface::class, [
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getStorage'  => $this->storage,
        ]);

        $this->resource = $this->getNewResource();
    }

    #[DataProvider('getStatuses')]
    public function testSetsCorrectStatusCodeAndErrorMessage(bool $databaseStatus, bool $storageStatus, ?int $statusCode = 0, ?string $reasonPhrase = ''): void
    {
        $this->database
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn($databaseStatus);
        $this->storage
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn($storageStatus);

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('addCacheControlDirective')
            ->with('no-store');

        $this->response->headers = $responseHeaders;

        if ($databaseStatus && $storageStatus) {
            $this->response
                ->expects($this->never())
                ->method('setStatusCode');
        } else {
            $this->response
                ->expects($this->once())
                ->method('setStatusCode')
                ->with($statusCode, $reasonPhrase);
        }

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(StatusModel::class));
        $this->response
            ->expects($this->once())
            ->method('setMaxAge')
            ->with(0)
            ->willReturnSelf();
        $this->response
            ->expects($this->once())
            ->method('setPrivate')
            ->willReturnSelf();

        $this->resource->get($this->event);
    }

    /**
     * @return array<string,array{databaseStatus:bool,storageStatus:bool,statusCode?:int,reasonPhrase?:string}>
     */
    public static function getStatuses(): array
    {
        return [
            'no error' => [
                'databaseStatus' => true,
                'storageStatus' => true,
            ],
            'database down' => [
                'databaseStatus' => false,
                'storageStatus' => true,
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE,
                'reasonPhrase' => 'Database error',
            ],
            'storage down' => [
                'databaseStatus' => true,
                'storageStatus' => false,
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE,
                'reasonPhrase' => 'Storage error',
            ],
            'both down' => [
                'databaseStatus' => false,
                'storageStatus' => false,
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE,
                'reasonPhrase' => 'Database and storage error',
            ],
        ];
    }
}
