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
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->manager = $this->getMockBuilder('Imbo\EventManager\EventManager')->disableOriginalConstructor()->getMock();

        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->manager));

        $this->resource = $this->getNewResource();
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
        $this->response = null;
        $this->database = null;
        $this->event = null;
        $this->manager = null;
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

        $route = $this->getMock('Imbo\Router\Route');
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
        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-ShortUrl', 'http://imbo/s/' . $id);
        $this->response->headers = $responseHeaders;

        $this->manager->expects($this->once())->method('trigger')->with('image.get');

        $this->resource->getImage($this->event);
    }

    /**
     * @covers Imbo\Resource\GlobalShortUrl::getImage
     * @expectedException Imbo\Exception\ResourceException
     * @expectedExceptionMessage Image not found
     * @expectedExceptionCode 404
     */
    public function testRespondsWith404WhenShortUrlDoesNotExist() {
        $route = $this->getMock('Imbo\Router\Route');
        $route->expects($this->once())->method('get')->with('shortUrlId')->will($this->returnValue('aaaaaaa'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $this->database->expects($this->once())->method('getShortUrlParams')->with('aaaaaaa')->will($this->returnValue(null));
        $this->resource->getImage($this->event);
    }
}
