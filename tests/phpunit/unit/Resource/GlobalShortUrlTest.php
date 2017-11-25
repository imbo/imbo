<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Resource;

use Imbo\Resource\GlobalShortUrl;
use Imbo\Exception\ResourceException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventManager;
use Imbo\EventManager\Event;
use Imbo\Router\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @covers Imbo\Resource\GlobalShortUrl
 * @coversDefaultClass Imbo\Resource\GlobalShortUrl
 * @group unit
 * @group resources
 */
class GlobalShortUrlTest extends ResourceTests {
    /**
     * @var GlobalShortUrl
     */
    private $resource;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var EventManager
     */
    private $manager;

    /**
     * @var Event
     */
    private $event;

    /**
     * {@inheritdoc}
     */
    protected function getNewResource() {
        return new GlobalShortUrl();
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->manager = $this->createMock(EventManager::class);

        $this->event = $this->createConfiguredMock(Event::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getManager' => $this->manager,
        ]);

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers ::getImage
     */
    public function testCanTriggerAnImageGetEventWhenRequestedWithAValidShortUrl() {
        $id = 'aaaaaaa';
        $user = 'christer';
        $imageIdentifier = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $extension = 'png';
        $query = [
            't' => [
                'thumbnail:width=40'
            ],
            'accessToken' => 'some token',
        ];

        $route = $this->createMock('Imbo\Router\Route');
        $route->expects($this->at(0))
              ->method('get')
              ->with('shortUrlId')
              ->willReturn($id);

        $route->expects($this->at(1))
              ->method('set')
              ->with('user', $user);

        $route->expects($this->at(2))
              ->method('set')
              ->with('imageIdentifier', $imageIdentifier);

        $route->expects($this->at(3))
              ->method('set')
              ->with('extension', $extension);

        $this->request->expects($this->once())
                      ->method('getRoute')
                      ->willReturn($route);

        $this->request->expects($this->once())
                      ->method('getUri')
                      ->willReturn(sprintf('http://imbo/s/%s', $id));

        $this->database->expects($this->once())
                       ->method('getShortUrlParams')
                       ->with($id)
                       ->willReturn([
                           'user' => $user,
                           'imageIdentifier' => $imageIdentifier,
                           'extension' => $extension,
                           'query' => $query,
                       ]);

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders->expects($this->once())
                        ->method('set')
                        ->with('X-Imbo-ShortUrl', sprintf('http://imbo/s/%s', $id));

        $this->response->headers = $responseHeaders;

        $this->manager->expects($this->once())
                      ->method('trigger')
                      ->with('image.get');

        $this->resource->getImage($this->event);
    }

    /**
     * @covers ::getImage
     */
    public function testRespondsWith404WhenShortUrlDoesNotExist() {
        $route = $this->createMock(Route::class);
        $route->expects($this->once())
              ->method('get')
              ->with('shortUrlId')
              ->willReturn('aaaaaaa');

        $this->request->expects($this->once())
                      ->method('getRoute')
                      ->willReturn($route);

        $this->database->expects($this->once())
                       ->method('getShortUrlParams')
                       ->with('aaaaaaa')
                       ->willReturn(null);

        $this->expectExceptionObject(new ResourceException('Image not found', 404));

        $this->resource->getImage($this->event);
    }
}
