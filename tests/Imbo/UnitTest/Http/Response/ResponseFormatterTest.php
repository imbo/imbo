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

    private $container;

    /**
     * Set up the response formatter
     *
     * @covers Imbo\Http\Response\ResponseFormatter::setContainer
     */
    public function setUp() {
        $this->container = $this->getMock('Imbo\Container');
        $this->responseFormatter = new ResponseFormatter();
        $this->responseFormatter->setContainer($this->container);
    }

    /**
     * Tear down the response
     */
    public function tearDown() {
        $this->container = null;
        $this->responseFormatter = null;
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::getDefinition
     */
    public function testReturnsACorrectDefinition() {
        $definition = $this->responseFormatter->getDefinition();
        $this->assertInternalType('array', $definition);

        foreach ($definition as $d) {
            $this->assertInstanceOf('Imbo\EventListener\ListenerDefinition', $d);
        }
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::send
     */
    public function testWritesAgainWhenFirstCallFails() {
        $exception = $this->getMock('Imbo\Exception\RuntimeException');
        $model = $this->getMock('Imbo\Model\ModelInterface');
        $error = $this->getMock('Imbo\Model\Error');
        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $response->expects($this->once())->method('createError')->with($exception, $request);
        $response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $response->expects($this->at(0))->method('getModel')->will($this->returnValue($model));
        $response->expects($this->at(3))->method('getModel')->will($this->returnValue($error));
        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));
        $responseWriter = $this->getMock('Imbo\Http\Response\ResponseWriter');
        $responseWriter->expects($this->at(0))->method('write')->with($model, $request, $response)->will($this->throwException($exception));
        $responseWriter->expects($this->at(1))->method('write')->with($error, $request, $response, false);
        $this->container->expects($this->once())->method('get')->with('responseWriter')->will($this->returnValue($responseWriter));

        $this->responseFormatter->send($event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::send
     */
    public function testReturnWhenStatusCodeIs204() {
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $response->expects($this->once())->method('getStatusCode')->will($this->returnValue(204));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $responseWriter = $this->getMock('Imbo\Http\Response\ResponseWriter');
        $responseWriter->expects($this->never())->method('write');

        $this->responseFormatter->send($event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::send
     */
    public function testReturnWhenThereIsNoModel() {
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $response->expects($this->once())->method('getModel')->will($this->returnValue(null));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $responseWriter = $this->getMock('Imbo\Http\Response\ResponseWriter');
        $responseWriter->expects($this->never())->method('write');

        $this->responseFormatter->send($event);
    }
}
