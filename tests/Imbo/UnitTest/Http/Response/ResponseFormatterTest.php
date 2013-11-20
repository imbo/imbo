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

use Imbo\Http\Response\ResponseFormatter,
    Imbo\Http\ContentNegotiation,
    Imbo\Model\Error,
    Imbo\Model\Image,
    Imbo\Router\Route,
    Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\Http\Response\ResponseFormatter
 */
class ResponseFormatterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ResponseFormatter
     */
    private $responseFormatter;

    private $formatters;
    private $contentNegotiation;
    private $formatter;
    private $request;
    private $response;
    private $event;

    /**
     * Set up the response formatter
     */
    public function setUp() {
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->formatter = $this->getMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $this->formatters = array(
            'format' => $this->formatter,
        );
        $this->contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');
        $this->responseFormatter = new ResponseFormatter($this->formatters, $this->contentNegotiation);
        $this->responseFormatter->setFormatter('format');
    }

    /**
     * Tear down the response
     */
    public function tearDown() {
        $this->responseFormatter = null;
        $this->formatters = array();
        $this->contentNegotiation = null;
        $this->formatter = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::getSubscribedEvents
     */
    public function testReturnsACorrectEventSubscription() {
        $class = get_class($this->responseFormatter);
        $this->assertInternalType('array', $class::getSubscribedEvents());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::format
     */
    public function testReturnWhenStatusCodeIs204() {
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(204));
        $this->formatter->expects($this->never())->method('format');
        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::format
     */
    public function testReturnWhenThereIsNoModel() {
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue(null));
        $this->formatter->expects($this->never())->method('format');

        $this->responseFormatter->format($this->event);
    }

    /**
     * Get different jsonp triggers
     *
     * @return array[]
     */
    public function getJsonpTriggers() {
        return array(
            array('callback', 'func', 'application/json'),
            array('json', 'func', 'application/json'),
            array('jsonp', 'func', 'application/json'),
            array('function', 'func', 'application/json', false),

            array('callback', 'func', 'application/xml', false),
            array('json', 'func', 'application/xml', false),
            array('jsonp', 'func', 'application/xml', false),
            array('function', 'func', 'application/xml', false),
        );
    }

    /**
     * @dataProvider getJsonpTriggers
     * @covers Imbo\Http\Response\ResponseFormatter::format
     */
    public function testCanWrapJsonDataInSpecifiedCallback($param, $callback, $contentType, $valid = true) {
        $json = '{"key":"value"}';
        $expectedContent = $json;

        if ($valid) {
            $expectedContent = sprintf('%s(%s)', $callback, $json);
        }

        $model = $this->getMock('Imbo\Model\ModelInterface');

        $this->formatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($json));
        $this->formatter->expects($this->once())->method('getContentType')->will($this->returnValue($contentType));

        $query = new ParameterBag(array(
            $param => $callback,
        ));

        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->query = $query;
        $this->response->headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->response->headers->expects($this->once())->method('add')->with(array(
            'Content-Type' => $contentType,
            'Content-Length' => strlen($expectedContent),
        ));
        $this->response->expects($this->once())->method('setContent')->with($expectedContent);
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($model));

        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::setFormatter
     * @covers Imbo\Http\Response\ResponseFormatter::getFormatter
     */
    public function testCanSetAndGetTheFormatter() {
        $formatter = 'some formatter';
        $this->assertSame($this->responseFormatter, $this->responseFormatter->setFormatter($formatter));
        $this->assertSame($formatter, $this->responseFormatter->getFormatter());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testDoesNotDoContentNegotiationWhenTheRequestedPathIncludesAnExtension() {
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('json'));
        $model = $this->getMock('Imbo\Model\Stats');
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($model));
        $this->contentNegotiation->expects($this->never())->method('isAcceptable');

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::format
     */
    public function testDoesNotSetResponseContentWhenHttpMethodIsHead() {
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->getMock('Imbo\Model\Stats')));
        $this->response->expects($this->never())->method('setContent');
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('HEAD'));

        $this->responseFormatter->format($this->event);
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Not acceptable
     * @expectedExceptionCode 406
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testThrowsAnExceptionInStrictModeWhenTheUserAgentDoesNotSupportAnyOfImbosMediaTypes() {
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));
        $this->response->expects($this->once())->method('setVary')->with('Accept');
        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('text/xml'));
        $this->request->headers = $requestHeaders;

        $this->responseFormatter->negotiate($this->event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testUsesDefaultMediaTypeInNonStrictModeWhenTheUserAgentDoesNotSupportAnyMediaTypes() {
        $this->event->expects($this->once())->method('hasArgument')->with('noStrict')->will($this->returnValue(true));
        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('text/xml'));
        $this->request->headers = $requestHeaders;
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));
        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testPicksThePrioritizedMediaTypeIfMoreThanOneWithSameQualityAreSupportedByTheUserAgent() {
        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('application/*'));
        $this->request->headers = $requestHeaders;
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));
        $this->response->expects($this->once())->method('setVary')->with('Accept');

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * Get mime types and the expected formatter
     *
     * @return array[]
     */
    public function getOriginalMimeTypes() {
        return array(
            'jpg' => array('image/jpeg', 'jpg'),
            'gif' => array('image/gif', 'gif'),
            'png' => array('image/png', 'png'),
        );
    }

    /**
     * @dataProvider getOriginalMimeTypes
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testUsesTheOriginalMimeTypeOfTheImageIfTheClientHasNoPreference($originalMimeType, $expectedFormatter) {
        // Use a real object since the code we are testing uses get_class(), which won't work as
        // expected when the object used is a mock
        $image = new Image();
        $image->setMimeType($originalMimeType);

        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('image/*'));
        $this->request->headers = $requestHeaders;
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));
        $this->response->expects($this->once())->method('setVary')->with('Accept');
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame($expectedFormatter, $this->responseFormatter->getFormatter());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testForcesContentNegotiationOnErrorModelsWhenResourceIsAnImage() {
        $route = new Route();
        $route->setName('image');

        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('*/*'));
        $this->request->headers = $requestHeaders;
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));

        $this->response->expects($this->once())->method('setVary')->with('Accept');
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->getMock('Imbo\Model\Error')));
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testDoesNotForceContentNegotiationOnErrorModelsWhenResourceIsNotAnImage() {
        $route = new Route();
        $route->setName('user');

        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('json'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $this->response->expects($this->never())->method('setVary');
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->getMock('Imbo\Model\Error')));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::format
     */
    public function testTriggersAConversionTransformationIfNeededWhenTheModelIsAnImage() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->exactly(2))->method('getMimeType')->will($this->returnValue('image/jpeg'));
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image blob'));

        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));
        $this->responseFormatter->setFormatter('png');

        $eventManager = $this->getMock('Imbo\EventManager\EventManager');
        $eventManager->expects($this->at(0))
                     ->method('trigger')
                     ->with(
                         'image.transformation.convert',
                         array(
                             'image' => $image,
                             'params' => array('type' => 'png'),
                         )
                     );
        $eventManager->expects($this->at(1))
                     ->method('trigger')
                     ->with('image.transformed', array('image' => $image));

        $this->event->expects($this->once())->method('getManager')->will($this->returnValue($eventManager));

        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::format
     */
    public function testDoesNotTriggerAnImageConversionWhenTheImageHasTheCorrectMimeType() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->at(0))->method('getMimeType')->will($this->returnValue('image/jpeg'));

        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));
        $this->responseFormatter->setFormatter('jpg');

        $eventManager = $this->getMock('Imbo\EventManager\EventManager');
        $eventManager->expects($this->once())
                     ->method('trigger')
                     ->with('image.transformed', array('image' => $image));

        $this->event->expects($this->once())->method('getManager')->will($this->returnValue($eventManager));

        $this->responseFormatter->format($this->event);
    }
}
