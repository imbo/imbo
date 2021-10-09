<?php declare(strict_types=1);
namespace Imbo\Http\Response;

use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\RuntimeException;
use Imbo\Http\ContentNegotiation;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Formatter\FormatterInterface;
use Imbo\Image\OutputConverterManager;
use Imbo\Image\TransformationManager;
use Imbo\Model\Error;
use Imbo\Model\Image;
use Imbo\Model\ModelInterface;
use Imbo\Model\Stats;
use Imbo\Router\Route;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @coversDefaultClass Imbo\Http\Response\ResponseFormatter
 */
class ResponseFormatterTest extends TestCase
{
    private $responseFormatter;
    private $formatters;
    private $contentNegotiation;
    private $formatter;
    private $request;
    private $response;
    private $event;
    private $outputConverterManager;
    private $transformationManager;

    public function setUp(): void
    {
        $defaultSupported = [];

        foreach ($this->getOriginalMimeTypes() as $type) {
            $defaultSupported[$type[0]] = $type[1];
        }

        $this->outputConverterManager = $this->createConfiguredMock(OutputConverterManager::class, [
            'getMimetypeToExtensionMap' => $defaultSupported,
            'getExtensionToMimetypeMap' => array_flip($defaultSupported),
            'getSupportedMimetypes' => array_keys($defaultSupported),
            'getSupportedExtensions' => array_values($defaultSupported),
            'supportsExtension' => $this->returnCallback(function ($ext) use ($defaultSupported) {
                return in_array($ext, $defaultSupported);
            }),
            'getMimetypeFromExtension' => $this->returnCallback(function ($ext) use ($defaultSupported) {
                return array_search($ext, $defaultSupported) ?: null;
            }),
            'getExtensionFromMimetype' => $this->returnCallback(function ($ext) use ($defaultSupported) {
                return isset($defaultSupported[$ext]) ? $defaultSupported[$ext] : null;
            }),
        ]);

        $this->transformationManager = $this->createConfiguredMock(TransformationManager::class, [
            'hasAppliedTransformations' => true,
        ]);
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getConfig' => ['contentNegotiateImages' => true],
            'getOutputConverterManager' => $this->outputConverterManager,
            'getTransformationManager' => $this->transformationManager,
        ]);
        $this->formatter = $this->createMock(FormatterInterface::class);
        $this->formatters = [
            'format' => $this->formatter,
        ];
        $this->contentNegotiation = $this->createMock(ContentNegotiation::class);
        $this->responseFormatter = new ResponseFormatter([
            'formatters' => $this->formatters,
            'contentNegotiation' => $this->contentNegotiation,
        ]);
        $this->responseFormatter->setFormatter('format');
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsACorrectEventSubscription(): void
    {
        $class = get_class($this->responseFormatter);
        $this->assertIsArray($class::getSubscribedEvents());
    }

    /**
     * @covers ::format
     */
    public function testReturnWhenStatusCodeIs204(): void
    {
        $this->response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_NO_CONTENT);

        $this->formatter
            ->expects($this->never())
            ->method('format');

        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers ::format
     */
    public function testReturnWhenThereIsNoModel(): void
    {
        $this->response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn(null);

        $this->formatter
            ->expects($this->never())
            ->method('format');

        $this->responseFormatter->format($this->event);
    }

    public function getJsonpTriggers(): array
    {
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
    public function testCanWrapJsonDataInSpecifiedCallback($param, $callback, $contentType, $valid = true): void
    {
        $json = '{"key":"value"}';
        $expectedContent = $json;

        if ($valid) {
            $expectedContent = sprintf('%s(%s)', $callback, $json);
        }

        $model = $this->createMock(ModelInterface::class);

        $this->formatter
            ->expects($this->once())
            ->method('format')
            ->with($model)
            ->willReturn($json);

        $this->formatter
            ->expects($this->once())
            ->method('getContentType')
            ->willReturn($contentType);

        $query = new ParameterBag([
            $param => $callback,
        ]);

        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->request->query = $query;
        $this->response->headers = $this->createMock(HeaderBag::class);
        $this->response->headers
            ->expects($this->once())
            ->method('add')
            ->with([
                'Content-Type' => $contentType,
                'Content-Length' => strlen($expectedContent),
            ]);

        $this->response
            ->expects($this->once())
            ->method('setContent')
            ->with($expectedContent);

        $this->response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($model);

        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers ::setFormatter
     * @covers ::getFormatter
     */
    public function testCanSetAndGetTheFormatter(): void
    {
        $formatter = 'some formatter';
        $this->assertSame($this->responseFormatter, $this->responseFormatter->setFormatter($formatter));
        $this->assertSame($formatter, $this->responseFormatter->getFormatter());
    }

    /**
     * @covers ::negotiate
     */
    public function testDoesNotDoContentNegotiationWhenTheRequestedPathIncludesAnExtension(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getExtension')
            ->willReturn('json');

        $model = $this->createMock(Stats::class);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($model);

        $this->contentNegotiation
            ->expects($this->never())
            ->method('isAcceptable');

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers ::format
     */
    public function testDoesNotSetResponseContentWhenHttpMethodIsHead(): void
    {
        $this->response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $model = $this->createMock(Stats::class);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($model);

        $this->response
            ->expects($this->never())
            ->method('setContent');

        $this->response->headers = $this->createMock(HeaderBag::class);

        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('HEAD');

        $this->request->query = $this->createMock(ParameterBagInterface::class);

        $this->formatter
            ->expects($this->once())
            ->method('format')
            ->with($model)
            ->willReturn('{"some":"data"}');

        $this->formatter
            ->expects($this->once())
            ->method('getContentType')
            ->willReturn('application/json');

        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers ::negotiate
     */
    public function testThrowsAnExceptionInStrictModeWhenTheUserAgentDoesNotSupportAnyOfImbosMediaTypes(): void
    {
        $this->contentNegotiation
            ->expects($this->any())
            ->method('isAcceptable')
            ->willReturn(false);

        $this->response
            ->expects($this->once())
            ->method('setVary')
            ->with('Accept');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($this->createMock(Image::class));

        $requestHeaders = $this->createMock(HeaderBag::class);
        $requestHeaders
            ->expects($this->once())
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('text/xml');

        $this->request->headers = $requestHeaders;
        $this->expectExceptionObject(new RuntimeException('Not acceptable', Response::HTTP_NOT_ACCEPTABLE));

        $this->responseFormatter->negotiate($this->event);
    }

    /**
     * @covers ::negotiate
     */
    public function testUsesDefaultMediaTypeInNonStrictModeWhenTheUserAgentDoesNotSupportAnyMediaTypes(): void
    {
        $this->event
            ->expects($this->once())
            ->method('hasArgument')
            ->with('noStrict')
            ->willReturn(true);

        $requestHeaders = $this->createMock(HeaderBag::class);
        $requestHeaders
            ->expects($this->once())
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('text/xml');

        $this->request->headers = $requestHeaders;

        $this->contentNegotiation
            ->expects($this->any())
            ->method('isAcceptable')
            ->willReturn(false);

        $this->response
            ->expects($this->once())
            ->method('setVary')
            ->with('Accept');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($this->createMock(Image::class));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers ::negotiate
     */
    public function testPicksThePrioritizedMediaTypeIfMoreThanOneWithSameQualityAreSupportedByTheUserAgent(): void
    {
        $requestHeaders = $this->createMock(HeaderBag::class);
        $requestHeaders
            ->expects($this->once())
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('application/*');

        $this->request->headers = $requestHeaders;

        $this->contentNegotiation
            ->expects($this->any())
            ->method('isAcceptable')
            ->willReturn(1);

        $this->response
            ->expects($this->once())
            ->method('setVary')
            ->with('Accept');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($this->createMock(Image::class));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    public function getOriginalMimeTypes(): array
    {
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
    public function testUsesTheOriginalMimeTypeOfTheImageIfTheClientHasNoPreference($originalMimeType, $expectedFormatter): void
    {
        // Use a real object since the code we are testing uses get_class(), which won't work as
        // expected when the object used is a mock
        $image = new Image();
        $image->setMimeType($originalMimeType);
        $image->setExtension($expectedFormatter);

        $requestHeaders = $this->createMock(HeaderBag::class);
        $requestHeaders
            ->expects($this->once())
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('image/*');

        $this->request->headers = $requestHeaders;

        $this->contentNegotiation
            ->expects($this->any())
            ->method('isAcceptable')
            ->willReturn(1);

        $this->response
            ->expects($this->once())
            ->method('setVary')
            ->with('Accept');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame($expectedFormatter, $this->responseFormatter->getFormatter());
    }

    /**
     * @dataProvider getOriginalMimeTypes
     * @covers ::negotiate
     */
    public function testUsesTheOriginalMimeTypeOfTheImageIfConfigDisablesContentNegotiationForImages($originalMimeType, $expectedFormatter): void
    {
        // Use a real object since the code we are testing uses get_class(), which won't work as
        // expected when the object used is a mock
        $image = new Image();
        $image->setMimeType($originalMimeType);
        $image->setExtension($expectedFormatter);

        $this->request->headers = $this->createMock(HeaderBag::class);

        $this->contentNegotiation
            ->expects($this->any())
            ->method('isAcceptable')
            ->willReturn(1);

        $this->response
            ->expects($this->never())
            ->method('setVary');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getConfig' => ['contentNegotiateImages' => false],
            'getOutputConverterManager' => $this->outputConverterManager,
        ]);

        $this->responseFormatter->negotiate($event);
        $this->assertSame($expectedFormatter, $this->responseFormatter->getFormatter());
    }

    public function getImageResources(): array
    {
        return [
            'image' => ['image'],
            'global short url' => ['globalshorturl'],
        ];
    }

    /**
     * @dataProvider getImageResources
     * @covers ::negotiate
     */
    public function testForcesContentNegotiationOnErrorModelsWhenResourceIsAnImage($routeName): void
    {
        $route = new Route();
        $route->setName($routeName);

        $this->request->headers = $this->createMock(HeaderBag::class);
        $this->request->headers
            ->expects($this->once())
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('*/*');

        $this->request
            ->expects($this->once())
            ->method('getExtension')
            ->willReturn('jpg');

        $this->request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);

        $this->response
            ->expects($this->once())
            ->method('setVary')
            ->with('Accept');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($this->createMock(Error::class));

        $this->contentNegotiation
            ->expects($this->any())
            ->method('isAcceptable')
            ->willReturn(1);

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers ::negotiate
     */
    public function testDoesNotForceContentNegotiationOnErrorModelsWhenResourceIsNotAnImage(): void
    {
        $route = new Route();
        $route->setName('user');

        $this->request
            ->expects($this->once())
            ->method('getExtension')
            ->willReturn('json');

        $this->request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);

        $this->response
            ->expects($this->never())
            ->method('setVary');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($this->createMock(Error::class));

        $this->responseFormatter->negotiate($this->event);
        $this->assertSame('json', $this->responseFormatter->getFormatter());
    }

    /**
     * @covers ::format
     */
    public function testTriggersAConversionTransformationIfNeededWhenTheModelIsAnImage(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getMimeType' => 'image/jpeg',
        ]);
        $image
            ->expects($this->once())
            ->method('getBlob')
            ->willReturn('image blob');

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $this->response->headers = $this->createMock(HeaderBag::class);
        $this->responseFormatter->setFormatter('png');

        $eventManager = $this->createMock(EventManager::class);
        $eventManager
            ->method('trigger')
            ->with('image.transformed', ['image' => $image]);

        $this->outputConverterManager
            ->expects($this->atLeastOnce())
            ->method('convert');

        $this->event
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($eventManager);

        $this->responseFormatter->format($this->event);
    }

    /**
     * @covers ::format
     */
    public function testDoesNotTriggerAnImageConversionWhenTheImageHasTheCorrectMimeType(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getMimeType' => 'image/jpeg',
            'getBlob' => 'image data',
        ]);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $this->response->headers = $this->createMock(HeaderBag::class);
        $this->responseFormatter->setFormatter('jpg');

        $eventManager = $this->createMock(EventManager::class);
        $eventManager
            ->expects($this->once())
            ->method('trigger')
            ->with('image.transformed', ['image' => $image]);

        $this->outputConverterManager
            ->expects($this->never())
            ->method('convert');

        $this->event
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($eventManager);

        $this->responseFormatter->format($this->event);
    }
}
