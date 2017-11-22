<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Http\Response;

use Imbo\Http\Response\ResponseFormatter;
use Imbo\Http\ContentNegotiation;
use Imbo\Model\Error;
use Imbo\Model\Image;
use Imbo\Router\Route;
use Symfony\Component\HttpFoundation\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Http\Response\ResponseFormatter
 * @group unit
 * @group http
 * @group formatters
 */
class ResponseFormatterTest extends TestCase {
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
    private $outputConverterManager;
    private $transformationManager;

    /**
     * Set up the response formatter
     */
    public function setUp() {
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getConfig')->will($this->returnValue(['contentNegotiateImages' => true]));
        $this->formatter = $this->createMock('Imbo\Http\Response\Formatter\FormatterInterface');
        $this->formatters = [
            'format' => $this->formatter,
        ];
        $this->contentNegotiation = $this->createMock('Imbo\Http\ContentNegotiation');
        $this->responseFormatter = new ResponseFormatter([
            'formatters' => $this->formatters,
            'contentNegotiation' => $this->contentNegotiation,
        ]);
        $this->responseFormatter->setFormatter('format');

        $defaultSupported = [];

        foreach ($this->getOriginalMimeTypes() as $type) {
            $defaultSupported[$type[0]] = $type[1];
        }

        $this->outputConverterManager = $this->createMock('Imbo\Image\OutputConverterManager');
        $this->outputConverterManager->method('getMimetypeToExtensionMap')->will($this->returnValue($defaultSupported));
        $this->outputConverterManager->method('getExtensionToMimetypeMap')->will($this->returnValue(array_flip($defaultSupported)));
        $this->outputConverterManager->method('getSupportedMimetypes')->will($this->returnValue(array_keys($defaultSupported)));
        $this->outputConverterManager->method('getSupportedExtensions')->will($this->returnValue(array_values($defaultSupported)));
        $this->outputConverterManager->method('supportsExtension')->will($this->returnCallback(function ($ext) use ($defaultSupported) {
            return in_array($ext, $defaultSupported);
        }));
        $this->outputConverterManager->method('getMimetypeFromExtension')->will($this->returnCallback(function ($ext) use ($defaultSupported) {
            return array_search($ext, $defaultSupported) ?: null;
        }));
        $this->outputConverterManager->method('getExtensionFromMimetype')->will($this->returnCallback(function ($ext) use ($defaultSupported) {
            return isset($defaultSupported[$ext]) ? $defaultSupported[$ext] : null;
        }));

        $this->event->expects($this->any())->method('getOutputConverterManager')->will($this->returnValue($this->outputConverterManager));

        $this->transformationManager = $this->createMock('Imbo\Image\TransformationManager');
        $this->transformationManager->method('hasAppliedTransformations')->will($this->returnValue(true));
        $this->event->expects($this->any())->method('getTransformationManager')->will($this->returnValue($this->transformationManager));
    }

    /**
     * Tear down the response
     */
    public function tearDown() {
        $this->responseFormatter = null;
        $this->formatters = [];
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
        return [
            ['callback', 'func', 'application/json'],
            ['json', 'func', 'application/json'],
            ['jsonp', 'func', 'application/json'],
            ['function', 'func', 'application/json', false],
        ];
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

        $model = $this->createMock('Imbo\Model\ModelInterface');

        $this->formatter->expects($this->once())->method('format')->with($model)->will($this->returnValue($json));
        $this->formatter->expects($this->once())->method('getContentType')->will($this->returnValue($contentType));

        $query = new ParameterBag([
            $param => $callback,
        ]);

        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->query = $query;
        $this->response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->response->headers->expects($this->once())->method('add')->with([
            'Content-Type' => $contentType,
            'Content-Length' => strlen($expectedContent),
        ]);
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
        $model = $this->createMock('Imbo\Model\Stats');
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
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->createMock('Imbo\Model\Stats')));
        $this->response->expects($this->never())->method('setContent');
        $this->response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
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
        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('text/xml'));
        $this->request->headers = $requestHeaders;

        $this->responseFormatter->negotiate($this->event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testUsesDefaultMediaTypeInNonStrictModeWhenTheUserAgentDoesNotSupportAnyMediaTypes() {
        $this->event->expects($this->once())->method('hasArgument')->with('noStrict')->will($this->returnValue(true));
        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
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
        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
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
        return [
            'jpg' => ['image/jpeg', 'jpg'],
            'gif' => ['image/gif', 'gif'],
            'png' => ['image/png', 'png'],
        ];
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
        $image->setExtension($expectedFormatter);

        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('image/*'));
        $this->request->headers = $requestHeaders;
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));
        $this->response->expects($this->once())->method('setVary')->with('Accept');
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame($expectedFormatter, $this->responseFormatter->getFormatter());
    }

    /**
     * @dataProvider getOriginalMimeTypes
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     */
    public function testUsesTheOriginalMimeTypeOfTheImageIfConfigDisablesContentNegotiationForImages($originalMimeType, $expectedFormatter) {
        // Use a real object since the code we are testing uses get_class(), which won't work as
        // expected when the object used is a mock
        $image = new Image();
        $image->setMimeType($originalMimeType);
        $image->setExtension($expectedFormatter);

        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->request->headers = $requestHeaders;
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));
        $this->response->expects($this->never())->method('setVary');
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $event->expects($this->any())->method('getConfig')->will($this->returnValue(['contentNegotiateImages' => false]));
        $event->expects($this->any())->method('getOutputConverterManager')->will($this->returnValue($this->outputConverterManager));

        $this->responseFormatter->negotiate($event);
        $this->assertSame($expectedFormatter, $this->responseFormatter->getFormatter());
    }

    public function getImageResources() {
        return [
            'image' => ['image'],
            'global short url' => ['globalshorturl'],
        ];
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::negotiate
     * @dataProvider getImageResources
     */
    public function testForcesContentNegotiationOnErrorModelsWhenResourceIsAnImage($routeName) {
        $route = new Route();
        $route->setName($routeName);

        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('*/*'));
        $this->request->headers = $requestHeaders;
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));

        $this->response->expects($this->once())->method('setVary')->with('Accept');
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->createMock('Imbo\Model\Error')));
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
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->createMock('Imbo\Model\Error')));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::format
     */
    public function testTriggersAConversionTransformationIfNeededWhenTheModelIsAnImage() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/jpeg'));
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image blob'));

        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));
        $this->response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->responseFormatter->setFormatter('png');

        $eventManager = $this->createMock('Imbo\EventManager\EventManager');
        $eventManager->expects($this->at(0))
                     ->method('trigger')
                     ->with('image.transformed', ['image' => $image]);

        $this->outputConverterManager->expects($this->atLeastOnce())->method('convert');

        $this->event->expects($this->once())->method('getManager')->will($this->returnValue($eventManager));

        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers Imbo\Http\Response\ResponseFormatter::format
     */
    public function testDoesNotTriggerAnImageConversionWhenTheImageHasTheCorrectMimeType() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/jpeg'));

        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));
        $this->response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->responseFormatter->setFormatter('jpg');

        $eventManager = $this->createMock('Imbo\EventManager\EventManager');
        $eventManager->expects($this->once())
                     ->method('trigger')
                     ->with('image.transformed', ['image' => $image]);

        $this->outputConverterManager->expects($this->never())->method('convert');

        $this->event->expects($this->once())->method('getManager')->will($this->returnValue($eventManager));

        $this->responseFormatter->format($this->event);
    }
}
