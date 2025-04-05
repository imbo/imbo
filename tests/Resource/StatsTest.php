<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(Stats::class)]
class StatsTest extends ResourceTests
{
    private Stats $resource;
    private Response&MockObject $response;
    private EventManager&MockObject $eventManager;
    private EventInterface&MockObject $event;

    protected function getNewResource(): Stats
    {
        return new Stats();
    }

    public function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->eventManager = $this->createMock(EventManager::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getResponse' => $this->response,
            'getManager' => $this->eventManager,
        ]);

        $this->resource = $this->getNewResource();
    }

    public function testTriggersTheCorrectEvent(): void
    {
        /** @var ResponseHeaderBag&MockObject */
        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('addCacheControlDirective')
            ->with('no-store');

        $this->response->headers = $responseHeaders;
        $this->response
            ->expects($this->once())
            ->method('setMaxAge')
            ->with(0)
            ->willReturnSelf();
        $this->response
            ->expects($this->once())
            ->method('setPrivate')
            ->willReturnSelf();

        $this->eventManager
            ->expects($this->once())
            ->method('trigger')
            ->with('db.stats.load');

        $this->resource->get($this->event);
    }
}
