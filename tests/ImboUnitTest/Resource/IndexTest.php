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

use Imbo\Resource\Index;

/**
 * @covers Imbo\Resource\Index
 * @group unit
 * @group resources
 */
class IndexTest extends ResourceTests {
    /**
     * @var Index
     */
    private $resource;

    private $request;
    private $response;
    private $event;

    /**
     * {@inheritdoc}
     */
    protected function getNewResource() {
        return new Index();
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->resource = $this->getNewResource();
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
    }

    /**
     * @covers Imbo\Resource\Index::get
     */
    public function testSupportsHttpGet() {
        $this->request->expects($this->once())->method('getSchemeAndHttpHost')->will($this->returnValue('http://imbo'));
        $this->request->expects($this->once())->method('getBaseUrl')->will($this->returnValue(''));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));
        $this->response->expects($this->once())->method('setMaxAge')->with(0)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setPrivate');

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $responseHeaders->expects($this->once())->method('addCacheControlDirective')->with('no-store');

        $this->response->headers = $responseHeaders;

        $this->resource->get($this->event);
    }
}
