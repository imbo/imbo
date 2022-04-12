<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @coversDefaultClass Imbo\EventListener\ResponseSender
 */
class ResponseSenderTest extends ListenerTests
{
    private $listener;

    public function setUp(): void
    {
        $this->listener = new ResponseSender();
    }

    protected function getListener(): ResponseSender
    {
        return $this->listener;
    }

    /**
     * @covers ::send
     */
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

        $response->headers = $this->createMock(HeaderBag::class);
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

    /**
     * @covers ::send
     */
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

        $response->headers = $this->createMock(HeaderBag::class);
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
