<?php declare(strict_types=1);
namespace ImboUnitTest\Http\Response;

use Imbo\Http\Response\ResponseFormatter;
use Imbo\Http\ContentNegotiation;
use Imbo\Model\Error;
use Imbo\Model\Image;
use Imbo\Router\Route;
use Imbo\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Http\Response\ResponseFormatter
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
    public function setUp() : void {
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
     * @covers ::getSubscribedEvents
     */
    public function testReturnsACorrectEventSubscription() : void {
        $class = get_class($this->responseFormatter);
        $this->assertIsArray($class::getSubscribedEvents());
    }

    /**
     * @covers ::format
     */
    public function testReturnWhenStatusCodeIs204() : void {
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(204));
        $this->formatter->expects($this->never())->method('format');
        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers ::format
     */
    public function testReturnWhenThereIsNoModel() : void {
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
     * @covers ::format
     */
    public function testCanWrapJsonDataInSpecifiedCallback($param, $callback, $contentType, $valid = true) : void {
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
     * @covers ::setFormatter
     * @covers ::getFormatter
     */
    public function testCanSetAndGetTheFormatter() : void {
        $formatter = 'some formatter';
        $this->assertSame($this->responseFormatter, $this->responseFormatter->setFormatter($formatter));
        $this->assertSame($formatter, $this->responseFormatter->getFormatter());
    }

    /**
     * @covers ::negotiate
     */
    public function testDoesNotDoContentNegotiationWhenTheRequestedPathIncludesAnExtension() : void {
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('json'));
        $model = $this->createMock('Imbo\Model\Stats');
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($model));
        $this->contentNegotiation->expects($this->never())->method('isAcceptable');

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers ::format
     */
    public function testDoesNotSetResponseContentWhenHttpMethodIsHead() : void {
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->createMock('Imbo\Model\Stats')));
        $this->response->expects($this->never())->method('setContent');
        $this->response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('HEAD'));

        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers ::negotiate
     */
    public function testThrowsAnExceptionInStrictModeWhenTheUserAgentDoesNotSupportAnyOfImbosMediaTypes() : void {
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));
        $this->response->expects($this->once())->method('setVary')->with('Accept');
        $this->response->expects($this->once())->method('getModel')->willReturn($this->createMock(Image::class));
        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('text/xml'));
        $this->request->headers = $requestHeaders;
        $this->expectExceptionObject(new RuntimeException('Not acceptable', 406));

        $this->responseFormatter->negotiate($this->event);
    }

    /**
     * @covers ::negotiate
     */
    public function testUsesDefaultMediaTypeInNonStrictModeWhenTheUserAgentDoesNotSupportAnyMediaTypes() : void {
        $this->event->expects($this->once())->method('hasArgument')->with('noStrict')->will($this->returnValue(true));
        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('text/xml'));
        $this->request->headers = $requestHeaders;
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(false));
        $this->response->expects($this->once())->method('setVary')->with('Accept');
        $this->response->expects($this->once())->method('getModel')->willReturn($this->createMock(Image::class));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers ::negotiate
     */
    public function testPicksThePrioritizedMediaTypeIfMoreThanOneWithSameQualityAreSupportedByTheUserAgent() : void {
        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept', '*/*')->will($this->returnValue('application/*'));
        $this->request->headers = $requestHeaders;
        $this->contentNegotiation->expects($this->any())->method('isAcceptable')->will($this->returnValue(1));
        $this->response->expects($this->once())->method('setVary')->with('Accept');
        $this->response->expects($this->once())->method('getModel')->willReturn($this->createMock(Image::class));

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
     * @covers ::negotiate
     */
    public function testUsesTheOriginalMimeTypeOfTheImageIfTheClientHasNoPreference($originalMimeType, $expectedFormatter) : void {
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
     * @covers ::negotiate
     */
    public function testUsesTheOriginalMimeTypeOfTheImageIfConfigDisablesContentNegotiationForImages($originalMimeType, $expectedFormatter) : void {
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
     * @covers ::negotiate
     * @dataProvider getImageResources
     */
    public function testForcesContentNegotiationOnErrorModelsWhenResourceIsAnImage($routeName) : void {
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
     * @covers ::negotiate
     */
    public function testDoesNotForceContentNegotiationOnErrorModelsWhenResourceIsNotAnImage() : void {
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
     * @covers ::format
     */
    public function testTriggersAConversionTransformationIfNeededWhenTheModelIsAnImage() : void {
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
     * @covers ::format
     */
    public function testDoesNotTriggerAnImageConversionWhenTheImageHasTheCorrectMimeType() : void {
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
