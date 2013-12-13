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

use Imbo\Resource\Status;

/**
 * @covers Imbo\Resource\Status
 * @group unit
 * @group resources
 */
class StatusTest extends ResourceTests {
    /**
     * @var Status
     */
    private $resource;

    private $response;
    private $database;
    private $storage;
    private $event;

    /**
     * {@inheritdoc}
     */
    protected function getNewResource() {
        return new Status();
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));

        $this->resource = $this->getNewResource();
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
        $this->response = null;
        $this->database = null;
        $this->storage = null;
        $this->event = null;
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testSetsCorrectStatusCodeAndErrorMessageWhenDatabaseFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(false));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(true));

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $responseHeaders->expects($this->once())->method('addCacheControlDirective')->with('no-store');

        $this->response->headers = $responseHeaders;
        $this->response->expects($this->once())->method('setStatusCode')->with(503, 'Database error');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Status'));
        $this->response->expects($this->once())->method('setMaxAge')->with(0)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setPrivate')->will($this->returnSelf());

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testSetsCorrectStatusCodeAndErrorMessageWhenStorageFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(true));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(false));

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $responseHeaders->expects($this->once())->method('addCacheControlDirective')->with('no-store');

        $this->response->headers = $responseHeaders;
        $this->response->expects($this->once())->method('setStatusCode')->with(503, 'Storage error');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Status'));
        $this->response->expects($this->once())->method('setMaxAge')->with(0)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setPrivate')->will($this->returnSelf());

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testSetsCorrectStatusCodeAndErrorMessageWhenBothDatabaseAndStorageFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(false));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(false));

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $responseHeaders->expects($this->once())->method('addCacheControlDirective')->with('no-store');

        $this->response->headers = $responseHeaders;
        $this->response->expects($this->once())->method('setStatusCode')->with(503, 'Database and storage error');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Status'));
        $this->response->expects($this->once())->method('setMaxAge')->with(0)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setPrivate')->will($this->returnSelf());

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testDoesNotUpdateStatusCodeWhenNoAdapterFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(true));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(true));

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $responseHeaders->expects($this->once())->method('addCacheControlDirective')->with('no-store');

        $this->response->headers = $responseHeaders;
        $this->response->expects($this->never())->method('setStatusCode');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Status'));
        $this->response->expects($this->once())->method('setMaxAge')->with(0)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setPrivate')->will($this->returnSelf());

        $this->resource->get($this->event);
    }
}
