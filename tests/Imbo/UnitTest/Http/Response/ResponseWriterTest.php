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
    Imbo\Model\Error,
    Imbo\Model\Image,
    Imbo\Model\ArrayModel,
    Imbo\Router\Route,
    Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class ResponseWriterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ResponseWriter
     */
    private $responseWriter;

    private $request;
    private $response;
    private $responseHeaders;
    private $requestHeaders;
    private $jpegFormatter;
    private $gifFormatter;
    private $pngFormatter;
    private $jsonFormatter;
    private $formatters;
    private $contentNegotiation;

    /**
     * Set up the response writer
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->headers = $this->responseHeaders;
        $this->request->headers = $this->requestHeaders;

        $this->jpegFormatter = $this->getMockBuilder('Imbo\Http\Response\Formatter\Jpeg')->disableOriginalConstructor()->getMock();
        $this->jpegFormatter->expects($this->any())->method('getContentType')->will($this->returnValue('image/jpeg'));

        $this->gifFormatter = $this->getMockBuilder('Imbo\Http\Response\Formatter\Gif')->disableOriginalConstructor()->getMock();
        $this->gifFormatter->expects($this->any())->method('getContentType')->will($this->returnValue('image/gif'));

        $this->pngFormatter = $this->getMockBuilder('Imbo\Http\Response\Formatter\Png')->disableOriginalConstructor()->getMock();
        $this->pngFormatter->expects($this->any())->method('getContentType')->will($this->returnValue('image/png'));

        $this->jsonFormatter = $this->getMockBuilder('Imbo\Http\Response\Formatter\JSON')->disableOriginalConstructor()->getMock();
        $this->jsonFormatter->expects($this->any())->method('getContentType')->will($this->returnValue('application/json'));

        $this->formatters = array(
            'jpeg' => $this->jpegFormatter,
            'gif' => $this->gifFormatter,
            'png' => $this->pngFormatter,
            'json' => $this->jsonFormatter,
        );
        $this->contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');

        $this->responseWriter = new ResponseWriter($this->formatters, $this->contentNegotiation);
    }

    /**
     * Tear down the response
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->responseHeaders = null;
        $this->responseWriter = null;
        $this->formatters = null;
        $this->contentNegotiation = null;
        $this->jpegFormatter = null;
        $this->gifFormatter = null;
        $this->pngFormatter = null;
        $this->jsonFormatter = null;
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testDoesNotDoContentNegotiationWhenTheRequestedPathIncludesAnExtension() {
        $formattedData = 'formatted data';
        $model = $this->getMock('Imbo\Model\Image');
        $this->jpegFormatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($formattedData));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'image/jpeg',
            'Content-Length' => strlen($formattedData),
        ));
        $this->response->expects($this->once())->method('setContent')->with($formattedData);

        $this->responseWriter->write($model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testDoesNotSetAResponseContentWhenHttpMethodIsHead() {
        $formattedData = 'formatted data';
        $model = $this->getMock('Imbo\Model\Image');
        $this->jpegFormatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($formattedData));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('HEAD'));
        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'image/jpeg',
            'Content-Length' => strlen($formattedData),
        ));
        $this->response->expects($this->never())->method('setContent');

        $this->responseWriter->write($model, $this->request, $this->response);
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
        $expectedContent = $json;

        if ($valid) {
            $expectedContent = $callback . '(' . $json . ')';
        }

        $model = $this->getMock('Imbo\Model\ModelInterface');

        $this->jsonFormatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($json));

        $query = new ParameterBag(array(
            $param => $callback,
        ));

        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('json'));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->query = $query;
        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($expectedContent),
        ));
        $this->response->expects($this->once())->method('setContent')->with($expectedContent);

        $this->responseWriter->write($model, $this->request, $this->response);
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Not acceptable
     * @expectedExceptionCode 406
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testThrowsAnExceptionInStrictModeWhenTheUserAgentDoesNotSupportAnyOfImbosMediaTypes() {
        $this->requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('text/xml'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));

        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));

        $this->responseWriter->write($this->getMock('Imbo\Model\ModelInterface'), $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testUsesDefaultMediaTypeInNonStrictModeWhenTheUserAgentDoesNotSupportAnyMediaTypes() {
        $json = '"data"';

        $query = new ParameterBag(array());

        $this->requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('text/xml'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->query = $query;

        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));

        $model = $this->getMock('Imbo\Model\ModelInterface');

        $this->jsonFormatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($json));

        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($json),
        ));

        $this->response->expects($this->once())->method('setContent')->with($json);
        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->responseWriter->write($model, $this->request, $this->response, false);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testPicksThePrioritizedMediaTypeIfMoreThanOneWithSameQualityAreSupportedByTheUserAgent() {
        $this->model = new ArrayModel();
        $formattedModel = '{"foo":"bar"}';

        $this->requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('application/*'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->query = new ParameterBag();

        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $model = $this->getMock('Imbo\Model\ModelInterface');

        $this->jsonFormatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($formattedModel));

        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($formattedModel),
        ));

        $this->response->expects($this->once())->method('setContent')->with($formattedModel);
        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->responseWriter->write($model, $this->request, $this->response);
    }

    /**
     * Get mime types and the expected formatter
     *
     * @return array[]
     */
    public function getOriginalMimeTypes() {
        return array(
            'jpeg' => array('image/jpeg', 'jpeg'),
            'gif' => array('image/gif', 'gif'),
            'png' => array('image/png', 'png'),
        );
    }

    /**
     * @dataProvider getOriginalMimeTypes
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testUsesTheOriginalMimeTypeOfTheImageIfTheClientHasNoPreferance($originalMimeType, $expectedFormatter) {
        $model = new Image();
        $model->setMimeType($originalMimeType);
        $imageData = 'some binary data';

        $this->requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('image/*'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $this->formatters[$expectedFormatter]->expects($this->once())->method('format')->will($this->returnValue($imageData));

        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => $originalMimeType,
            'Content-Length' => strlen($imageData),
        ));

        $this->response->expects($this->once())->method('setContent')->with($imageData);
        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->responseWriter->write($model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testForcesContentNegotiationOnErrorModelsWhenResourceIsAnImage() {
        $model = new Error();
        $error = '{"some":"error"}';
        $query = new ParameterBag();

        $route = new Route();
        $route->setName('image');

        $this->requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('*/*'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $this->request->query = $query;

        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->jsonFormatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($error));

        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $this->responseWriter->write($model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testDoesNotForcesContentNegotiationOnErrorModelsWhenResourceIsNotAnImage() {
        $model = new Error();
        $error = '{"some":"error"}';
        $query = new ParameterBag();

        $route = new Route();
        $route->setName('user');

        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('json'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $this->request->query = $query;

        $this->jsonFormatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($error));

        $this->responseWriter->write($model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testAcceptsAllMediaTypesWhenAcceptIsEmpty() {
        $model = new Error();
        $error = '{"some":"error"}';

        // Mimic a missing $_SERVER['HTTP_ACCEPT'] value
        $this->requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('*/*'));

        // Make sure the missing value falls back to */*
        $this->request->query = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');

        $this->jsonFormatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($error));

        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $this->responseWriter->write($model, $this->request, $this->response);
    }
}
