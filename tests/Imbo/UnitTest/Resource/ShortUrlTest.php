<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Resource;

use Imbo\Resource\ShortUrl;

/**
 * @covers Imbo\Resource\ShortUrl
 * @group unit
 * @group resources
 */
class ShortUrlTest extends ResourceTests {
    /**
     * @var ShortUrl
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
        return new ShortUrl();
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
     * @covers Imbo\Resource\ShortUrl::addShortUrlHeader
     */
    public function testDoesNotAddShortUrlIfResponseAlreadyHasOne() {
        $headers = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $headers->expects($this->once())->method('has')->with('X-Imbo-ShortUrl')->will($this->returnValue(true));
        $this->response->headers = $headers;

        $this->resource->addShortUrlHeader($this->event);
    }

    /**
     * @covers Imbo\Resource\ShortUrl::addShortUrlHeader
     * @covers Imbo\Resource\ShortUrl::getShortUrlId
     */
    public function testWillGenerateAShortUrlIdUntilItFindsAValidOne() {
        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $responseHeaders->expects($this->once())->method('has')->with('X-Imbo-ShortUrl')->will($this->returnValue(false));
        $this->response->headers = $responseHeaders;

        $publicKey = 'christer';
        $imageIdentifier = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $extension = null;
        $query = array();

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue($extension));
        $requestQuery = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $requestQuery->expects($this->once())->method('all')->will($this->returnValue($query));
        $this->request->query = $requestQuery;

        $this->database->expects($this->at(0))
                       ->method('getShortUrlId')
                       ->with($publicKey, $imageIdentifier, $extension, $query)
                       ->will($this->returnValue(null));
        $this->database->expects($this->at(1))
                       ->method('getShortUrlParams')
                       ->with($this->isType('string'))
                       ->will($this->returnValue(array('publicKey' => 'some key')));
        $this->database->expects($this->at(2))
                       ->method('getShortUrlParams')
                       ->with($this->isType('string'))
                       ->will($this->returnValue(array('publicKey' => 'some other key')));
        $this->database->expects($this->at(3))
                       ->method('getShortUrlParams')
                       ->with($this->isType('string'))
                       ->will($this->returnValue(null));
        $this->database->expects($this->at(4))
                       ->method('insertShortUrl')
                       ->with($this->isType('string'), $publicKey, $imageIdentifier, $extension, $query)
                       ->will($this->returnValue(null));

        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-ShortUrl', $this->isType('string'));

        $this->resource->addShortUrlHeader($this->event);
    }

    /**
     * @covers Imbo\Resource\ShortUrl::deleteShortUrls
     */
    public function testCanDeleteShortUrls() {
        $publicKey = 'christer';
        $imageIdentifier = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->database->expects($this->once())->method('deleteShortUrls')->with($publicKey, $imageIdentifier);
        $this->resource->deleteShortUrls($this->event);
    }

    /**
     * @covers Imbo\Resource\ShortUrl::get
     */
    public function testCanTriggerAnImageGetEventWhenRequestedWithAValidShortUrl() {
        $id = 'aaaaaaa';
        $publicKey = 'christer';
        $imageIdentifier = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $extension = 'png';
        $query = array(
            't' => array(
                'thumbnail:width=40'
            ),
            'accessToken' => 'some token',
        );

        $route = $this->getMock('Imbo\Router\Route');
        $route->expects($this->at(0))->method('get')->with('shortUrlId')->will($this->returnValue($id));
        $route->expects($this->at(1))->method('set')->with('publicKey', $publicKey);
        $route->expects($this->at(2))->method('set')->with('imageIdentifier', $imageIdentifier);
        $route->expects($this->at(3))->method('set')->with('extension', $extension);
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $this->request->expects($this->once())->method('getUri')->will($this->returnValue('http://imbo/s/' . $id));
        $this->database->expects($this->once())->method('getShortUrlParams')->with($id)->will($this->returnValue(array(
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'extension' => $extension,
            'query' => $query,
        )));
        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-ShortUrl', 'http://imbo/s/' . $id);
        $this->response->headers = $responseHeaders;

        $this->manager->expects($this->once())->method('trigger')->with('image.get');

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\ShortUrl::get
     * @expectedException Imbo\Exception\ResourceException
     * @expectedExceptionMessage Image not found
     * @expectedExceptionCode 404
     */
    public function testRespondsWith404WhenShortUrlDoesNotExist() {
        $route = $this->getMock('Imbo\Router\Route');
        $route->expects($this->once())->method('get')->with('shortUrlId')->will($this->returnValue('aaaaaaa'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $this->database->expects($this->once())->method('getShortUrlParams')->with('aaaaaaa')->will($this->returnValue(null));
        $this->resource->get($this->event);
    }
}
