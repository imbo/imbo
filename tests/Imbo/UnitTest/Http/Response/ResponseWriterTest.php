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

use Imbo\Http\Response\ResponseWriter,
    Imbo\Http\ParameterContainer,
    Imbo\Model\Image,
    Imbo\Model\ArrayModel;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class ResponseWriterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ResponseWriter
     */
    private $responseWriter;

    private $container;
    private $model;
    private $request;
    private $response;
    private $responseHeaders;

    /**
     * Set up the response writer
     *
     * @covers Imbo\Http\Response\ResponseWriter::setContainer
     */
    public function setUp() {
        $this->container = $this->getMock('Imbo\Container');
        $this->model = $this->getMock('Imbo\Model\ModelInterface');
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->response->expects($this->any())->method('getHeaders')->will($this->returnValue($this->responseHeaders));
        $this->responseWriter = new ResponseWriter();
        $this->responseWriter->setContainer($this->container);
    }

    /**
     * Tear down the response
     */
    public function tearDown() {
        $this->container = null;
        $this->model = null;
        $this->request = null;
        $this->response = null;
        $this->responseHeaders = null;
        $this->responseWriter = null;
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testDoesNotDoContentNegotiationWhenTheRequestedPathIncludesAnExtension() {
        $formattedData = 'formatted data';
        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($formattedData));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('image/jpeg'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->never())->method('getAcceptableContentTypes');
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->container->expects($this->once())->method('get')->with('jpegFormatter')->will($this->returnValue($formatter));
        $this->responseHeaders->expects($this->at(0))->method('set')->with('Content-Type', 'image/jpeg')->will($this->returnSelf());
        $this->responseHeaders->expects($this->at(1))->method('set')->with('Content-Length', strlen($formattedData))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setBody')->with($formattedData);

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testDoesNotSetAResponseBodyWhenHttpMethodIsHead() {
        $formattedData = 'formatted data';
        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($formattedData));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('image/jpeg'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->never())->method('getAcceptableContentTypes');
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('HEAD'));
        $this->container->expects($this->once())->method('get')->with('jpegFormatter')->will($this->returnValue($formatter));
        $this->responseHeaders->expects($this->at(0))->method('set')->with('Content-Type', 'image/jpeg')->will($this->returnSelf());
        $this->responseHeaders->expects($this->at(1))->method('set')->with('Content-Length', strlen($formattedData))->will($this->returnSelf());
        $this->response->expects($this->never())->method('setBody');

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }

    /**
     * Get different jsonp triggers
     *
     * @return array[]
     */
    public function getJsonpTriggers() {
        return array(
            array('callback', 'func'),
            array('json', 'func'),
            array('jsonp', 'func', ),
            array('function', 'func', false),
        );
    }

    /**
     * @dataProvider getJsonpTriggers
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testCanWrapJsonDataInSpecifiedCallback($param, $callback, $valid = true) {
        $json = '{"key":"value"}';
        $expectedBody = $json;

        if ($valid) {
            $expectedBody = $callback . '(' . $json . ')';
        }

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($json));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('application/json'));

        $query = new ParameterContainer(array(
            $param => $callback,
        ));

        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('json'));
        $this->request->expects($this->never())->method('getAcceptableContentTypes');
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $this->container->expects($this->once())->method('get')->with('jsonFormatter')->will($this->returnValue($formatter));
        $this->responseHeaders->expects($this->at(0))->method('set')->with('Content-Type', 'application/json')->will($this->returnSelf());
        $this->responseHeaders->expects($this->at(1))->method('set')->with('Content-Length', strlen($expectedBody))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setBody')->with($expectedBody);

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Not acceptable
     * @expectedExceptionCode 406
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testThrowsAnExceptionInStrictModeWhenTheUserAgentDoesNotSupportAnyOfImbosMediaTypes() {
        $acceptableTypes = array(
            'text/xml' => 1,
        );

        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue($acceptableTypes));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testUsesDefaultMediaTypeInNonStrictModeWhenTheUserAgentDoesNotSupportAnyMediaTypes() {
        $acceptableTypes = array(
            'text/xml' => 1,
        );

        $json = '"data"';

        $query = new ParameterContainer(array());

        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue($acceptableTypes));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($query));

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($json));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('application/json'));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));
        $this->container->expects($this->at(1))->method('get')->with('jsonFormatter')->will($this->returnValue($formatter));

        $this->responseHeaders->expects($this->at(0))->method('set')->with('Vary', 'Accept')->will($this->returnSelf());
        $this->responseHeaders->expects($this->at(1))->method('set')->with('Content-Type', 'application/json')->will($this->returnSelf());
        $this->responseHeaders->expects($this->at(2))->method('set')->with('Content-Length', strlen($json))->will($this->returnSelf());

        $this->response->expects($this->once())->method('setBody')->with($json);

        $this->responseWriter->write($this->model, $this->request, $this->response, false);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testPicksThePrioritizedMediaTypeIfMoreThanOneWithSameQualityAreSupportedByTheUserAgent() {
        $this->model = new Image();

        $acceptableTypes = array(
            'image/*' => 1,
        );

        $imageData = 'binary image data';

        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue($acceptableTypes));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($imageData));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('image/jpeg'));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));
        $this->container->expects($this->at(1))->method('get')->with('jpegFormatter')->will($this->returnValue($formatter));

        $this->responseHeaders->expects($this->at(0))->method('set')->with('Vary', 'Accept')->will($this->returnSelf());
        $this->responseHeaders->expects($this->at(1))->method('set')->with('Content-Type', 'image/jpeg')->will($this->returnSelf());
        $this->responseHeaders->expects($this->at(2))->method('set')->with('Content-Length', strlen($imageData))->will($this->returnSelf());

        $this->response->expects($this->once())->method('setBody')->with($imageData);

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }
}
