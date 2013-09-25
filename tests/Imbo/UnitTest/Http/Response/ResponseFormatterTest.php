<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http\Response;

use Imbo\Http\Response\ResponseFormatter;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class ResponseFormatterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ResponseFormatter
     */
    private $responseFormatter;

    private $responseWriter;

    /**
     * Set up the response formatter
     */
    public function setUp() {
        $this->responseWriter = $this->getMockBuilder('Imbo\Http\Response\ResponseWriter')->disableOriginalConstructor()->getMock();
        $this->responseFormatter = new ResponseFormatter($this->responseWriter);
    }

    /**
     * Tear down the response
     */
    public function tearDown() {
        $this->responseWriter = null;
        $this->responseFormatter = null;
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::getSubscribedEvents
     */
    public function testReturnsACorrectEventSubscription() {
        $class = get_class($this->responseFormatter);
        $this->assertInternalType('array', $class::getSubscribedEvents());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::send
     */
    public function testWritesAgainWhenFirstCallFails() {
        $exception = $this->getMock('Imbo\Exception\RuntimeException');
        $model = $this->getMock('Imbo\Model\ModelInterface');
        $request = $this->getMock('Imbo\Http\Request\Request');
        $response = $this->getMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $response->expects($this->once())->method('getModel')->will($this->returnValue($model));
        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));
        $this->responseWriter->expects($this->at(0))->method('write')->with($model, $request, $response)->will($this->throwException($exception));
        $this->responseWriter->expects($this->at(1))->method('write')->with($this->isInstanceOf('Imbo\Model\Error'), $request, $response, false);

        $this->responseFormatter->send($event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::send
     */
    public function testReturnWhenStatusCodeIs204() {
        $response = $this->getMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('getStatusCode')->will($this->returnValue(204));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $this->responseWriter->expects($this->never())->method('write');

        $this->responseFormatter->send($event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::send
     */
    public function testReturnWhenThereIsNoModel() {
        $response = $this->getMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $response->expects($this->once())->method('getModel')->will($this->returnValue(null));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $this->responseWriter->expects($this->never())->method('write');

        $this->responseFormatter->send($event);
    }
}
