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
    private $route;
    private $response;
    private $database;
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
        $this->resource = $this->getNewResource();
        $this->route = $this->getMock('Imbo\Router\Route');
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->request->expects($this->any())->method('getRoute')->will($this->returnValue($this->route));
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->event = $this->getMock('Imbo\EventManager\Event');

        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
        $this->request = null;
        $this->response = null;
        $this->database = null;
        $this->route = null;
        $this->event = null;
    }

    /**
     * @expectedException Imbo\Exception\ResourceException
     * @expectedExceptionMessage ShortURL not found
     * @expectedExceptionCode 404
     */
    public function testThrowsAnExceptionWhenTheShortUrlDoesNotExist() {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->route->expects($this->once())->method('get')->with('shortUrlId')->will($this->returnValue('aaaaaaa'));
        $this->database->expects($this->once())->method('getShortUrlParams')->with('aaaaaaa')->will($this->returnValue(null));

        $this->getNewResource()->deleteShortUrl($this->event);
    }

    /**
     * @expectedException Imbo\Exception\ResourceException
     * @expectedExceptionMessage ShortURL not found
     * @expectedExceptionCode 404
     */
    public function testThrowsAnExceptionWhenUserOrPrivateKeyDoesNotMatch() {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->route->expects($this->once())->method('get')->with('shortUrlId')->will($this->returnValue('aaaaaaa'));
        $this->database->expects($this->once())->method('getShortUrlParams')->with('aaaaaaa')->will($this->returnValue([
            'user' => 'otheruser',
            'imageIdentifier' => 'id',
        ]));

        $this->getNewResource()->deleteShortUrl($this->event);
    }

    public function testCanDeleteAShortUrl() {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->route->expects($this->once())->method('get')->with('shortUrlId')->will($this->returnValue('aaaaaaa'));
        $this->database->expects($this->once())->method('getShortUrlParams')->with('aaaaaaa')->will($this->returnValue([
            'user' => 'user',
            'imageIdentifier' => 'id',
        ]));
        $this->database->expects($this->once())->method('deleteShortUrls')->with('user', 'id', 'aaaaaaa');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));

        $this->getNewResource()->deleteShortUrl($this->event);
    }
}
