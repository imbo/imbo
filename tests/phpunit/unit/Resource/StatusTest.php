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
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->database = $this->createMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->createMock('Imbo\Storage\StorageInterface');
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testSetsCorrectStatusCodeAndErrorMessageWhenDatabaseFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(false));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(true));

        $responseHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
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

        $responseHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
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

        $responseHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
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

        $responseHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $responseHeaders->expects($this->once())->method('addCacheControlDirective')->with('no-store');

        $this->response->headers = $responseHeaders;
        $this->response->expects($this->never())->method('setStatusCode');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Status'));
        $this->response->expects($this->once())->method('setMaxAge')->with(0)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setPrivate')->will($this->returnSelf());

        $this->resource->get($this->event);
    }
}
