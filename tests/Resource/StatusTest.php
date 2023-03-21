<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Http\Response\Response;
use Imbo\Model\Status as StatusModel;
use Imbo\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @coversDefaultClass Imbo\Resource\Status
 */
class StatusTest extends ResourceTests
{
    private $resource;
    private $response;
    private $database;
    private $storage;
    private $event;

    protected function getNewResource(): Status
    {
        return new Status();
    }

    public function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getStorage'  => $this->storage,
        ]);

        $this->resource = $this->getNewResource();
    }

    public static function getStatuses(): array
    {
        return [
            'no error' => [
                true,
                true,
            ],
            'database down' => [
                false,
                true,
                Response::HTTP_SERVICE_UNAVAILABLE,
                'Database error',
            ],
            'storage down' => [
                true,
                false,
                Response::HTTP_SERVICE_UNAVAILABLE,
                'Storage error',
            ],
            'both down' => [
                false,
                false,
                Response::HTTP_SERVICE_UNAVAILABLE,
                'Database and storage error',
            ],
        ];
    }

    /**
     * @dataProvider getStatuses
     * @covers ::get
     */
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

        $responseHeaders = $this->createMock(HeaderBag::class);
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
}
