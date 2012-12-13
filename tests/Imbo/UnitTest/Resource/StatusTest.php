<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest\Resource;

use Imbo\Resource\Status;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Resource\Status
 */
class StatusTest extends ResourceTests {
    /**
     * @var Status
     */
    private $resource;

    private $container;
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
     *
     * @covers Imbo\Resource\Status::setContainer
     */
    public function setUp() {
        $this->container = $this->getMock('Imbo\Container');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));

        $this->resource = $this->getNewResource();
        $this->resource->setContainer($this->container);
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->container = null;
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

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->with('Cache-Control', 'max-age=0')->will($this->returnSelf());
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setStatusCode')->with(500)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setStatusMessage')->with('Database error');
        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));
        $this->container->expects($this->once())->method('get')->with('dateFormatter')->will($this->returnValue($this->getMock('Imbo\Helpers\DateFormatter')));

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testSetsCorrectStatusCodeAndErrorMessageWhenStorageFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(true));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(false));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->with('Cache-Control', 'max-age=0')->will($this->returnSelf());
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setStatusCode')->with(500)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setStatusMessage')->with('Storage error');
        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));
        $this->container->expects($this->once())->method('get')->with('dateFormatter')->will($this->returnValue($this->getMock('Imbo\Helpers\DateFormatter')));

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testSetsCorrectStatusCodeAndErrorMessageWhenBothDatabaseAndStorageFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(false));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(false));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->with('Cache-Control', 'max-age=0')->will($this->returnSelf());
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setStatusCode')->with(500)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setStatusMessage')->with('Database and storage error');
        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));
        $this->container->expects($this->once())->method('get')->with('dateFormatter')->will($this->returnValue($this->getMock('Imbo\Helpers\DateFormatter')));

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testDoesNotUpdateStatusCodeWhenNoAdapterFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(true));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(true));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->with('Cache-Control', 'max-age=0')->will($this->returnSelf());
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->never())->method('setStatusCode');
        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));
        $this->container->expects($this->once())->method('get')->with('dateFormatter')->will($this->returnValue($this->getMock('Imbo\Helpers\DateFormatter')));

        $this->resource->get($this->event);
    }
}
