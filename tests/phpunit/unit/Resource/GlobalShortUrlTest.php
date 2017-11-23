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

/**
 * @covers Imbo\Resource\GlobalShortUrl
 * @group unit
 * @group resources
 */
class GlobalShortUrlTest extends ResourceTests {
    /**
     * @var GlobalShortUrl
     */
    private $resource;

    private $request;
    private $response;
    private $database;
    private $manager;
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
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->database = $this->createMock('Imbo\Database\DatabaseInterface');
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->manager = $this->createMock('Imbo\EventManager\EventManager');

        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->manager));

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers Imbo\Resource\GlobalShortUrl::getImage
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
        $route->expects($this->at(0))->method('get')->with('shortUrlId')->will($this->returnValue($id));
        $route->expects($this->at(1))->method('set')->with('user', $user);
        $route->expects($this->at(2))->method('set')->with('imageIdentifier', $imageIdentifier);
        $route->expects($this->at(3))->method('set')->with('extension', $extension);
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $this->request->expects($this->once())->method('getUri')->will($this->returnValue('http://imbo/s/' . $id));
        $this->database->expects($this->once())->method('getShortUrlParams')->with($id)->will($this->returnValue([
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
            'extension' => $extension,
            'query' => $query,
        ]));
        $responseHeaders = $this->createMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-ShortUrl', 'http://imbo/s/' . $id);
        $this->response->headers = $responseHeaders;

        $this->manager->expects($this->once())->method('trigger')->with('image.get');

        $this->resource->getImage($this->event);
    }

    /**
     * @covers Imbo\Resource\GlobalShortUrl::getImage
     */
    public function testRespondsWith404WhenShortUrlDoesNotExist() {
        $route = $this->createMock('Imbo\Router\Route');
        $route->expects($this->once())->method('get')->with('shortUrlId')->will($this->returnValue('aaaaaaa'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $this->database->expects($this->once())->method('getShortUrlParams')->with('aaaaaaa')->will($this->returnValue(null));
        $this->expectExceptionObject(new ResourceException('Image not found', 404));
        $this->resource->getImage($this->event);
    }
}
