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

use Imbo\Resource\Stats;

/**
 * @covers Imbo\Resource\Stats
 * @group unit
 * @group resources
 */
class StatsTest extends ResourceTests {
    /**
     * @var Stats
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
        return new Stats();
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->eventManager = $this->getMockBuilder('Imbo\EventManager\EventManager')->disableOriginalConstructor()->getMock();
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->eventManager));

        $this->resource = $this->getNewResource();
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
        $this->response = null;
        $this->event = null;
        $this->eventManager = null;
    }

    /**
     * @covers Imbo\Resource\Stats::get
     */
    public function testTriggersTheCorrectEvent() {
        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $responseHeaders->expects($this->once())->method('addCacheControlDirective')->with('no-store');

        $this->response->headers = $responseHeaders;
        $this->response->expects($this->once())->method('setMaxAge')->with(0)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setPrivate')->will($this->returnSelf());

        $this->eventManager->expects($this->once())->method('trigger')->with('db.stats.load');

        $this->resource->get($this->event);
    }
}
