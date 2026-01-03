<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(ResponseSender::class)]
class ResponseSenderTest extends ListenerTests
{
    private ResponseSender $listener;

    public function setUp(): void
    {
        $this->listener = new ResponseSender();
    }

    protected function getListener(): ResponseSender
    {
        return $this->listener;
    }

    public function testCanSendTheResponse(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn('checksum');

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('isNotModified')
            ->with($request);

        $response
            ->expects($this->once())
            ->method('send');

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-ImageIdentifier', 'checksum');
        $response->headers = $headers;

        $event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $request,
            'getResponse' => $response,
        ]);

        $this->listener->send($event);
    }

    public function testCanSendTheResponseAndInjectTheCorrectImageIdentifier(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn('checksum');

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('isNotModified')
            ->with($request);

        $response
            ->expects($this->once())
            ->method('send');

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-ImageIdentifier', 'checksum');
        $response->headers = $headers;

        $event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $request,
            'getResponse' => $response,
        ]);

        $this->listener->send($event);
    }
}
