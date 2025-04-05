<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
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
        /** @var Image&MockObject */
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn('checksum');

        /** @var Request&MockObject */
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        /** @var Response&MockObject */
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('isNotModified')
            ->with($request);

        $response
            ->expects($this->once())
            ->method('send');

        /** @var ResponseHeaderBag&MockObject */
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-ImageIdentifier', 'checksum');

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $request,
            'getResponse' => $response,
        ]);

        $this->listener->send($event);
    }

    public function testCanSendTheResponseAndInjectTheCorrectImageIdentifier(): void
    {
        /** @var Request&MockObject */
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn('checksum');

        /** @var Response&MockObject */
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('isNotModified')
            ->with($request);

        $response
            ->expects($this->once())
            ->method('send');

        /** @var ResponseHeaderBag&MockObject */
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-ImageIdentifier', 'checksum');

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $request,
            'getResponse' => $response,
        ]);

        $this->listener->send($event);
    }
}
