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
use Imbo\Exception\ResourceException;

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
        $this->route = $this->createMock('Imbo\Router\Route');
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->request->expects($this->any())->method('getRoute')->will($this->returnValue($this->route));
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->database = $this->createMock('Imbo\Database\DatabaseInterface');
        $this->event = $this->createMock('Imbo\EventManager\Event');

        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
    }

    public function testThrowsAnExceptionWhenTheShortUrlDoesNotExist() {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->route->expects($this->once())->method('get')->with('shortUrlId')->will($this->returnValue('aaaaaaa'));
        $this->database->expects($this->once())->method('getShortUrlParams')->with('aaaaaaa')->will($this->returnValue(null));

        $this->expectExceptionObject(new ResourceException('ShortURL not found', 404));
        $this->getNewResource()->deleteShortUrl($this->event);
    }

    public function testThrowsAnExceptionWhenUserOrPrivateKeyDoesNotMatch() {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->route->expects($this->once())->method('get')->with('shortUrlId')->will($this->returnValue('aaaaaaa'));
        $this->database->expects($this->once())->method('getShortUrlParams')->with('aaaaaaa')->will($this->returnValue([
            'user' => 'otheruser',
            'imageIdentifier' => 'id',
        ]));

        $this->expectExceptionObject(new ResourceException('ShortURL not found', 404));
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
