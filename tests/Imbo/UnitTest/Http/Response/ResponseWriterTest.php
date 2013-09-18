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

    private $container;
    private $model;
    private $request;
    private $response;
    private $responseHeaders;
    private $requestHeaders;

    /**
     * Set up the response writer
     *
     * @covers Imbo\Http\Response\ResponseWriter::setContainer
     */
    public function setUp() {
        $this->container = $this->getMock('Imbo\Container');
        $this->model = $this->getMock('Imbo\Model\ModelInterface');
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->headers = $this->responseHeaders;
        $this->request->headers = $this->requestHeaders;

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
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->container->expects($this->once())->method('get')->with('jpegFormatter')->will($this->returnValue($formatter));
        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'image/jpeg',
            'Content-Length' => strlen($formattedData),
        ));
        $this->response->expects($this->once())->method('setContent')->with($formattedData);

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testDoesNotSetAResponseContentWhenHttpMethodIsHead() {
        $formattedData = 'formatted data';
        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($formattedData));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('image/jpeg'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('HEAD'));
        $this->container->expects($this->once())->method('get')->with('jpegFormatter')->will($this->returnValue($formatter));
        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'image/jpeg',
            'Content-Length' => strlen($formattedData),
        ));
        $this->response->expects($this->never())->method('setContent');

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
        $expectedContent = $json;

        if ($valid) {
            $expectedContent = $callback . '(' . $json . ')';
        }

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($json));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('application/json'));

        $query = new ParameterBag(array(
            $param => $callback,
        ));

        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('json'));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->query = $query;
        $this->container->expects($this->once())->method('get')->with('jsonFormatter')->will($this->returnValue($formatter));
        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($expectedContent),
        ));
        $this->response->expects($this->once())->method('setContent')->with($expectedContent);

        $this->responseWriter->write($this->model, $this->request, $this->response);
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

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));

        $this->responseWriter->write($this->model, $this->request, $this->response);
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

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($json));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('application/json'));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));
        $this->container->expects($this->at(1))->method('get')->with('jsonFormatter')->will($this->returnValue($formatter));

        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($json),
        ));

        $this->response->expects($this->once())->method('setContent')->with($json);
        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->responseWriter->write($this->model, $this->request, $this->response, false);
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

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($formattedModel));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('application/json'));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));
        $this->container->expects($this->at(1))->method('get')->with('jsonFormatter')->will($this->returnValue($formatter));

        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($formattedModel),
        ));

        $this->response->expects($this->once())->method('setContent')->with($formattedModel);
        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }

    /**
     * Get mime types and the expected formatter
     *
     * @return array[]
     */
    public function getOriginalMimeTypes() {
        return array(
            'jpeg' => array('image/jpeg', 'jpegFormatter'),
            'gif' => array('image/gif', 'gifFormatter'),
            'png' => array('image/png', 'pngFormatter'),
        );
    }

    /**
     * @dataProvider getOriginalMimeTypes
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testUsesTheOriginalMimeTypeOfTheImageIfTheClientHasNoPreferance($originalMimeType, $expectedFormatter) {
        $this->model = new Image();
        $this->model->setMimeType($originalMimeType);
        $imageData = 'some binary data';

        $this->requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('image/*'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($imageData));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue($originalMimeType));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));
        $this->container->expects($this->at(1))->method('get')->with($expectedFormatter)->will($this->returnValue($formatter));

        $this->responseHeaders->expects($this->once())->method('add')->with(array(
            'Content-Type' => $originalMimeType,
            'Content-Length' => strlen($imageData),
        ));

        $this->response->expects($this->once())->method('setContent')->with($imageData);
        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testForcesContentNegotiationOnErrorModelsWhenResourceIsAnImage() {
        $this->model = new Error();
        $error = '{"some":"error"}';
        $query = new ParameterBag();

        $this->requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('*/*'));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->once())->method('getResource')->will($this->returnValue('image'));
        $this->request->query = $query;

        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($error));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('application/json'));

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));
        $this->container->expects($this->at(1))->method('get')->with('jsonFormatter')->will($this->returnValue($formatter));

        $this->responseWriter->write($this->model, $this->request, $this->response);
    }

    /**
     * @covers Imbo\Http\Response\ResponseWriter::write
     */
    public function testDoesNotForcesContentNegotiationOnErrorModelsWhenResourceIsNotAnImage() {
        $this->model = new Error();
        $error = '{"some":"error"}';
        $query = new ParameterBag();

        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('json'));
        $this->request->expects($this->once())->method('getResource')->will($this->returnValue('user'));
        $this->request->query = $query;

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($this->model)->will($this->returnValue($error));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('application/json'));

        $this->container->expects($this->once())->method('get')->with('jsonFormatter')->will($this->returnValue($formatter));

        $this->responseWriter->write($this->model, $this->request, $this->response);
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

        $formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $formatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($error));
        $formatter->expects($this->once())->method('getContentType')->will($this->returnValue('application/json'));

        $contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $this->container->expects($this->at(0))->method('get')->with('contentNegotiation')->will($this->returnValue($contentNegotiation));
        $this->container->expects($this->at(1))->method('get')->with('jsonFormatter')->will($this->returnValue($formatter));

        $this->responseWriter->write($model, $this->request, $this->response);
    }
}
