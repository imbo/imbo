<?php declare(strict_types=1);
namespace Imbo\Behat;

use Assert;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Suite\Suite;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\Exception\AssertionFailedException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

#[CoversClass(FeatureContext::class)]
class FeatureContextTest extends TestCase
{
    private FeatureContext $context;
    /**
     * @var array<array{request:Request,response:Response}>
     */
    private array $history;
    private MockHandler $mockHandler;
    private HandlerStack $handlerStack;
    private string $baseUri = 'http://localhost:8080';
    private string $publicKey = 'publicKey';
    private string $privateKey = 'privateKey';

    public function setUp(): void
    {
        $this->history = [];

        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->history));

        $this->context = new FeatureContext();
        $this->context->initializeClient([
            'handler' => $this->handlerStack,
            'base_uri' => $this->baseUri,
        ]);
    }

    /**
     * Convenience method to make a single request and return the request instance
     */
    private function makeRequest(string $path = '/somepath'): Request
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath($path);

        return $this->history[count($this->history) - 1]['request'];
    }

    public function testCanSetAnApiClient(): void
    {
        /** @var HandlerStack&MockObject */
        $handlerStack = $this->createMock(HandlerStack::class);
        $handlerStack
            ->expects($this->exactly(2))
            ->method('push')
            ->with($this->isInstanceOf('Closure'), $this->isString());

        $context = new FeatureContext();
        $this->assertSame($context, $context->initializeClient(['handler' => $handlerStack, 'base_uri' => $this->baseUri]));
    }

    public function testAttachesComparatorFunctions(): void
    {
        /** @var ArrayContainsComparator&MockObject */
        $comparator = $this->createMock(ArrayContainsComparator::class);
        $comparator
            ->expects($this->once())
            ->method('addFunction')
            ->with($this->isString(), $this->isArray());
        $this->assertSame($this->context, $this->context->setArrayContainsComparator($comparator));
    }

    public function testCanSetRequestHeader(): void
    {
        $this->assertSame($this->context, $this->context->setRequestHeader('X-Foo', 'current-timestamp'));
        $this->assertSame($this->context, $this->context->setRequestHeader('X-Bar', 'current'));

        $request = $this->makeRequest();

        $this->assertTrue(
            (bool) preg_match(
                '/^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z$/',
                $request->getHeaderLine('X-Foo'),
            ),
            'setRequestHeader does not support the magic "current-timestamp" value.',
        );
        $this->assertSame('current', $request->getHeaderLine('X-Bar'));
    }

    public function testIsDateFunctionValidatesDates(): void
    {
        $this->assertNull($this->context->isDate('Wed, 15 Mar 2017 21:28:14 GMT'));
    }

    public function testIsDateFunctionCanFail(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Date is not properly formatted: "invalid date".'));
        $this->context->isDate('invalid date');
    }

    #[DataProvider('getImboConfigFiles')]
    public function testCanSetAConfigHeader(string $path): void
    {
        $this->assertSame($this->context, $this->context->setImboConfigHeader($path));
        $this->assertSame($path, $this->makeRequest()->getHeaderLine('X-Imbo-Test-Config-File'));
    }

    public function testSettingConfigHeaderFailsWithNonExistingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '|Configuration file "foobar" does not exist in the ".*?[\\/]imbo-configs" directory\.|',
        );

        $this->context->setImboConfigHeader('foobar');
    }

    public function testCanSetStatsAllowedByHeader(): void
    {
        $this->assertSame($this->context, $this->context->statsAllowedBy('*'));
        $this->assertSame('*', $this->makeRequest()->getHeaderLine('X-Imbo-Stats-Allowed-By'));
    }

    #[DataProvider('getAdaptersForFailure')]
    public function testCanForceAdapterFailureBySettingAHeader(string $adapter, string $header): void
    {
        $this->assertSame($this->context, $this->context->forceAdapterFailure($adapter));
        $this->assertSame('1', $this->makeRequest()->getHeaderLine($header));
    }

    public function testThrowsExecptionWhenSpecifyingInvalidAdapterForFailure(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Invalid adapter: "foobar".'));
        $this->context->forceAdapterFailure('foobar');
    }

    public function testCanSignRequest(): void
    {
        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey($this->publicKey, $this->privateKey)
                ->signRequest(),
        );
        $path = '/path';
        $request = $this->makeRequest($path);

        // Generate the URI and make sure the request URI is the same
        /** @var array{query:string} */
        $uri = parse_url((string) $request->getUri());
        $query = [];
        parse_str($uri['query'], $query);

        $data = sprintf(
            '%s|%s|%s|%s',
            $request->getMethod(),
            sprintf('%s%s?publicKey=%s', $this->baseUri, $path, $this->publicKey),
            $this->publicKey,
            (string) $query['timestamp'],
        );
        $signature = hash_hmac('sha256', $data, $this->privateKey);

        $this->assertSame($this->publicKey, $query['publicKey']);
        $this->assertSame($signature, $query['signature'], 'Signature mismatch.');
    }

    public function testCanSignRequestUsingHttpHeaders(): void
    {
        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey($this->publicKey, $this->privateKey)
                ->signRequestUsingHttpHeaders(),
        );
        $path = '/path';
        $request = $this->makeRequest($path);

        $this->assertTrue($request->hasHeader('X-Imbo-PublicKey'));
        $this->assertTrue($request->hasHeader('X-Imbo-Authenticate-Signature'));
        $this->assertTrue($request->hasHeader('X-Imbo-Authenticate-Timestamp'));

        $data = sprintf(
            '%s|%s|%s|%s',
            $request->getMethod(),
            sprintf('%s%s', $this->baseUri, $path),
            $this->publicKey,
            $request->getHeaderLine('X-Imbo-Authenticate-Timestamp'),
        );
        $signature = hash_hmac('sha256', $data, $this->privateKey);

        $this->assertSame($this->publicKey, $request->getHeaderLine('X-Imbo-PublicKey'));
        $this->assertSame($signature, $request->getHeaderLine('X-Imbo-Authenticate-Signature'), 'Signature mismatch.');
    }

    public function testCanNotAttachSignatureHandlerMoreThanOnce(): void
    {
        $this->context->signRequest();
        $this->expectExceptionObject(new RuntimeException(
            'The authentication handler is currently added to the stack. It can not be added more than once.',
        ));
        $this->context->signRequest();
    }

    #[DataProvider('getDataForAccessTokens')]
    public function testCanAppendAccessToken(string $path, string $expectedUrl): void
    {
        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey($this->publicKey, $this->privateKey)
                ->appendAccessToken(),
        );
        $request = $this->makeRequest($path);

        // Generate the URI and make sure the request URI is the same
        $this->assertSame($expectedUrl, (string) $request->getUri());
    }

    public function testCanNotAttachAccessTokenHandlerMoreThanOnce(): void
    {
        $this->context->appendAccessToken();
        $this->expectExceptionObject(new RuntimeException(
            'The access token handler is currently added to the stack. It can not be added more than once.',
        ));
        $this->context->appendAccessToken();
    }

    public function testCanNotAddBothAccessTokenAndSignatureHandlers(): void
    {
        $this->context->appendAccessToken();
        $this->expectExceptionObject(new RuntimeException(
            'The access token handler is currently added to the stack. These handlers should not be added to the same request.',
        ));
        $this->context->signRequest();
    }

    public function testCanNotAddBothSignatureAndAccessTokenHandlers(): void
    {
        $this->context->signRequest();
        $this->expectExceptionObject(new RuntimeException(
            'The authentication handler is currently added to the stack. These handlers should not be added to the same request.',
        ));
        $this->context->appendAccessToken();
    }

    public function testThrowsExceptionWhenAddingUserImageWithUnknownUser(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'No keys exist for user "some user".',
        ));
        $this->context->addUserImageToImbo(__FILE__, 'some user');
    }

    public function testThrowsExceptionWhenAddingUserImageWithInvalidFilename(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'File does not exist: "/some/path".',
        ));
        $this->context->addUserImageToImbo('/some/path', 'user');
    }

    public function testAddingUserImageToImboFailsWhenImboDoesNotIncludeImageIdentifierInResponse(): void
    {
        $this->mockHandler->append(new Response(400, ['Content-Type' => 'application/json'], '{"error": {"message": "some id"}}'));
        $this->expectExceptionObject(new RuntimeException(
            'Image was not successfully added. Response body:',
        ));
        $this->context->addUserImageToImbo(FIXTURES_DIR . '/image1.png', 'user');
    }

    public function testCanAddUserImageToImbo(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], '{"imageIdentifier": "some id"}'));

        $this->assertSame(
            $this->context,
            $this->context->addUserImageToImbo(FIXTURES_DIR . '/image1.png', 'user'),
        );

        $this->assertSame(
            1,
            $num = count($this->history),
            sprintf('There should be exactly 1 transction in the history, found %d.', $num),
        );

        $request = $this->history[0]['request'];

        $this->assertStringStartsWith(
            'http://localhost:8080/users/user/images?publicKey=publicKey&signature=',
            (string) $request->getUri(),
        );
        $this->assertSame('POST', $request->getMethod());
    }

    public function testCanAddUserImageWithMetadataToImbo(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"imageIdentifier": "imageId"}'),
            new Response(200),
        );

        $this->assertSame(
            $this->context,
            $this->context->addUserImageToImbo(
                FIXTURES_DIR . '/image1.png',
                'user',
                new PyStringNode(['{"foo": "bar"}'], 1),
            ),
        );

        $this->assertSame(
            2,
            $num = count($this->history),
            sprintf('There should be exactly 2 transctions in the history, found %d.', $num),
        );

        $imageRequest = $this->history[0]['request'];
        $metadataRequest = $this->history[1]['request'];

        $this->assertStringStartsWith(
            'http://localhost:8080/users/user/images?publicKey=publicKey&signature=',
            (string) $imageRequest->getUri(),
        );
        $this->assertSame('POST', $imageRequest->getMethod());

        $this->assertStringStartsWith(
            'http://localhost:8080/users/user/images/imageId/metadata?publicKey=publicKey&signature=',
            (string) $metadataRequest->getUri(),
        );
        $this->assertSame('POST', $metadataRequest->getMethod());
    }

    public function testCanSetClientIpHeader(): void
    {
        $ip = '1.2.3.4';
        $this->assertSame(
            $this->context,
            $this->context->setClientIp($ip),
        );
        $request = $this->makeRequest('/path');

        $this->assertTrue($request->hasHeader('X-Client-Ip'));
        $this->assertSame($ip, $request->getHeaderLine('X-Client-Ip'));
    }

    public function testCanApplyImageTransformation(): void
    {
        $this->assertSame(
            $this->context,
            $this->context->applyTransformation('t1'),
        );

        $request = $this->makeRequest('/path');

        $this->assertSame(
            'http://localhost:8080/path?t%5B0%5D=t1',
            (string) $request->getUri(),
        );
    }

    public function testCanApplyImageTransformations(): void
    {
        $this->assertSame(
            $this->context,
            $this->context->applyTransformations(new PyStringNode(['t1', 't2', 't3'], 1)),
        );

        $request = $this->makeRequest('/path');

        $this->assertSame(
            'http://localhost:8080/path?t%5B0%5D=t1&t%5B1%5D=t2&t%5B2%5D=t3',
            (string) $request->getUri(),
        );
    }

    public function testThrowsExceptionWhenPrimingDatabaseWithScriptThatDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '|Fixture file "foobar.php" does not exist in ".*?[\\/]features[\\/]fixtures"\.|',
        );
        $this->context->primeDatabase('foobar.php');
    }

    public function testThrowsExceptionWhenSpecifyingInvalidAuthenticationType(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid authentication method: "auth".',
        ));
        $this->context->authenticateRequest('auth');
    }

    #[DataProvider('getAuthDetails')]
    public function testCanUseDifferentAuthenticationMethods(string $publicKey, string $privateKey, string $authMethod, string $uriRegExp, array $headers = []): void
    {
        $this->assertSame(
            $this->context,
            $this->context->setPublicAndPrivateKey($publicKey, $privateKey),
        );
        $this->assertSame(
            $this->context,
            $this->context->authenticateRequest($authMethod),
        );

        $request = $this->makeRequest('/path');
        $this->assertMatchesRegularExpression($uriRegExp, (string) $request->getUri());

        foreach ($headers as $name => $regExp) {
            $this->assertTrue($request->hasHeader($name));
            $this->assertMatchesRegularExpression($regExp, $request->getHeaderLine($name));
        }
    }

    #[DataProvider('getRequestQueryParams')]
    public function testCanSetRequestQueryParameters(array $params, string $uri): void
    {
        foreach ($params as $param) {
            $this->assertSame(
                $this->context,
                $this->context->setRequestQueryParameter($param['name'], $param['value']),
            );
        }

        $this->assertSame($uri, (string) $this->makeRequest('/path')->getUri());
    }

    public function testThrowsExceptionWhenAppendingArrayParamToRegularParam(): void
    {
        $this->context->setRequestQueryParameter('t', 'border');
        $this->expectExceptionObject(new InvalidArgumentException(
            'The "t" query parameter already exists and it\'s not an array, so can\'t append more values to it.',
        ));
        $this->context->setRequestQueryParameter('t[]', 'thumb');
    }

    public function testThrowsExceptionWhenSettingARequestParameterToAnNonExistingImageIdentifier(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'No image identifier exists for image: "/path".',
        ));
        $this->context->setRequestParameterToImageIdentifier('foo', '/path');
    }

    public function testCanSetQueryParameterToImageIdentifier(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"imageIdentifier": "1"}'),
            new Response(200, [], '{"imageIdentifier": "2"}'),
            new Response(200, [], '{"imageIdentifier": "3"}'),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->addUserImageToImbo(FIXTURES_DIR . '/image1.png', 'user')
                ->addUserImageToImbo(FIXTURES_DIR . '/image2.png', 'user')
                ->addUserImageToImbo(FIXTURES_DIR . '/image3.png', 'user'),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->setRequestParameterToImageIdentifier('id1', FIXTURES_DIR . '/image1.png')
                ->setRequestParameterToImageIdentifier('id2', FIXTURES_DIR . '/image2.png')
                ->setRequestParameterToImageIdentifier('id3', FIXTURES_DIR . '/image3.png'),
        );

        $this->assertSame(
            'http://localhost:8080/path?id1=1&id2=2&id3=3',
            (string) $this->makeRequest('/path')->getUri(),
        );
    }

    public function testThrowsExceptionWheyGeneratingShortImageUrlForNonExistingImage(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'No image identifier exists for path: "/path".',
        ));
        $this->context->generateShortImageUrl('/path', new PyStringNode([], 1));
    }

    #[DataProvider('getShortUrlParams')]
    public function testCanGenerateShortUrls(string $image, string $user, string $imageIdentifier, array $params): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['imageIdentifier' => $imageIdentifier])),
            new Response(200),
        );

        $this->assertSame(
            $this->context,
            $this->context->addUserImageToImbo($image, $user),
        );

        $this->assertSame(
            $this->context,
            $this->context->generateShortImageUrl(
                $image,
                new PyStringNode([json_encode($params)], 1),
            ),
        );

        $this->assertCount(
            2,
            $this->history,
            'There should exist exactly 2 requests in the history, found %d.',
        );

        $request = $this->history[1]['request'];

        $this->assertSame(
            sprintf('http://localhost:8080/users/user/images/%s/shorturls', $imageIdentifier),
            (string) $request->getUri(),
        );

        $this->assertSame('POST', $request->getMethod());

        $this->assertSame(
            array_merge($params, ['imageIdentifier' => $imageIdentifier]),
            json_decode((string) $request->getBody(), true),
        )
        ;
    }

    public function testThrowsExceptionWhenSpecifyingWatermarkImageThatDoesNotExist(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'No image exists for path: "/path".',
        ));
        $this->context->specifyAsTheWatermarkImage('/path');
    }

    #[DataProvider('getDataForWatermarkImages')]
    public function testCanSpecifyWatermarkImage(string $image, string $imageIdentifier, string $params = null, string $uri): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['imageIdentifier' => $imageIdentifier])),
            new Response(200),
        );

        $this->assertSame(
            $this->context,
            $this->context->addUserImageToImbo($image, 'user'),
        );

        $this->assertSame(
            $this->context,
            $this->context->specifyAsTheWatermarkImage($image, $params),
        );

        $request = $this->makeRequest('/path');

        $this->assertSame($uri, (string) $request->getUri());
    }

    public function testThrowsExceptionWhenTryingToRequestPreviouslyAddedImageWhenNoImageHasBeenAdded(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException(
            'Could not find any response in the history with an image identifier.',
        ));
        $this->context->requestPreviouslyAddedImage();
    }

    #[DataProvider('getDataForRequestingPreviouslyAddedImage')]
    public function testCanRequestPreviouslyAddedImage(string $imageIdentifier, string $image, string $method): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['imageIdentifier' => $imageIdentifier])),
            new Response(200),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->addUserImageToImbo($image, 'user')
                ->requestPreviouslyAddedImage($method),
        );

        $this->assertCount(
            2,
            $this->history,
            sprintf('Expected exactly 2 requests, got: %d.', count($this->history)),
        );

        $request = $this->history[1]['request'];

        $this->assertSame($method, $request->getMethod());

        $this->assertSame(
            sprintf(
                'http://localhost:8080/users/user/images/%s',
                $imageIdentifier,
            ),
            (string) $request->getUri(),
        );
    }

    #[DataProvider('getDataForRequestingPreviouslyAddedImageWithExtension')]
    public function testCanRequestPreviouslyAddedImageUsingAlternativeMethod(string $imageIdentifier, string $image, string $method, string $extension): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['imageIdentifier' => $imageIdentifier])),
            new Response(200),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->addUserImageToImbo($image, 'user')
                ->requestPreviouslyAddedImageAsType($extension, $method),
        );

        $this->assertCount(
            2,
            $this->history,
            sprintf('Expected exactly 2 requests, got: %d.', count($this->history)),
        );

        $request = $this->history[1]['request'];

        $this->assertSame($method, $request->getMethod());

        $this->assertSame(
            sprintf(
                'http://localhost:8080/users/user/images/%s%s',
                $imageIdentifier,
                $extension ? '.' . $extension : '',
            ),
            (string) $request->getUri(),
        );
    }

    public function testThrowsExceptionWhenTryingToReplayRequestWhenNoRequestHasBeenMade(): void
    {
        $this->expectExceptionObject(new RuntimeException('No request has been made yet.'));
        $this->context->makeSameRequest();
    }

    #[DataProvider('getDataForReplayingRequests')]
    public function testCanReplayTheLastRequest(string $originalMethod, string $method = null, string $expectedUrl, string $publicKey = null, string $privateKey = null): void
    {
        $this->mockHandler->append(new Response(200), new Response(200));

        if (null !== $publicKey && null !== $privateKey) {
            $this->context->setPublicAndPrivateKey($publicKey, $privateKey);
            $this->context->appendAccessToken();
        }

        $this->context->requestPath('/path', $originalMethod);

        $this->assertSame(
            $this->context,
            $this->context->makeSameRequest($method),
        );

        $this->assertCount(
            2,
            $this->history,
            sprintf('Expected exactly 2 requests, got: %d.', count($this->history)),
        );

        $this->assertSame($originalMethod, $this->history[0]['request']->getMethod());
        $this->assertSame($method ?: $originalMethod, $this->history[1]['request']->getMethod());
        $this->assertSame($expectedUrl, (string) $this->history[0]['request']->getUri());
        $this->assertSame(
            (string) $this->history[0]['request']->getUri(),
            (string) $this->history[1]['request']->getUri(),
        );
    }

    public function testThrowsExceptionWhenRequestingMetadataOfPreviouslyAddedImageWhenNoImageHasBeenAdded(): void
    {
        $this->makeRequest('/path');
        $this->makeRequest('/anotherPath');
        $this->expectExceptionObject(new RuntimeException(
            'Could not find any response in the history with an image identifier.',
        ));
        $this->context->requestMetadataOfPreviouslyAddedImage();
    }

    public function testThrowsExceptionWhenRequestingMetadataOfPreviouslyAddedImageWhenNoRequestHasBeenMade(): void
    {
        $this->expectExceptionObject(new RuntimeException(
            'Could not find any response in the history with an image identifier.',
        ));
        $this->context->requestMetadataOfPreviouslyAddedImage();
    }

    #[DataProvider('getDataForRequestingMetadataOfPreviouslyAddedImage')]
    public function testCanRequestMetadataOfPreviouslyAddedImage(string $imageIdentifier, string $image, string $method, array $metadata): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['imageIdentifier' => $imageIdentifier])),
            new Response(200, [], json_encode($metadata)),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->addUserImageToImbo($image, 'user')
                ->requestMetadataOfPreviouslyAddedImage($method),
        );

        $this->assertCount(
            2,
            $this->history,
            sprintf('Expected exactly 2 requests, got: %d.', count($this->history)),
        );

        $request = $this->history[1]['request'];

        $this->assertSame($method, $request->getMethod());

        $this->assertSame(
            sprintf(
                'http://localhost:8080/users/user/images/%s/metadata',
                $imageIdentifier,
            ),
            (string) $request->getUri(),
        );
        $this->assertSame($metadata, json_decode((string) $this->history[1]['response']->getBody(), true));
    }

    public function testThrowsExceptionWhenTryingToRequestImageUsingLocalPathAndImageDoesNotExistInImbo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '|Image URL for image with path ".*?[\\/]tests[\\/]Fixtures[\\/]image1\.png" can not be found\.|',
        );
        $this->context->requestImageResourceForLocalImage(FIXTURES_DIR . '/image1.png');
    }

    #[DataProvider('getDataForRequestingImageWithLocalPath')]
    public function testCanRequestImageUsingLocalFilePath(string $image, string $imageIdentifier, ?string $extension, string $method, string $expectedPath): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['imageIdentifier' => $imageIdentifier])),
            new Response(200),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->addUserImageToImbo($image, 'user')
                ->requestImageResourceForLocalImage($image, $extension, $method),
        );

        $this->assertCount(
            2,
            $this->history,
            sprintf('Expected exactly 2 transactions in the history, got %d.', count($this->history)),
        );

        $request = $this->history[1]['request'];

        $this->assertSame($expectedPath, $request->getUri()->getPath());
        $this->assertSame($method, $request->getMethod());
    }

    public function testThrowsExceptionWhenBulkRequestingWithMissingPath(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Missing or empty "path" key.'));
        $this->context->requestPaths(new TableNode([
            ['method'],
            ['GET'],
        ]));
    }

    public function testThrowsExceptionWhenBulkRequestingAndUsingBothAccessTokenAndSignature(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Both "sign request" and "access token" can not be set to "yes".',
        ));
        $this->context->requestPaths(new TableNode([
            ['path',  'access token', 'sign request'],
            ['/path', 'yes',          'yes'         ],
        ]));
    }



    #[DataProvider('getDataForBulkRequests')]
    public function testCanBulkRequest(TableNode $table, array $requests): void
    {
        for ($i = 0; $i < count($table->getRows()) - 1; $i++) {
            $this->mockHandler->append(new Response(200));
        }

        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey('publicKey', 'privateKey')
                ->requestPaths($table),
        );

        foreach ($requests as $i => $data) {
            $this->assertSame($data['path'], $this->history[$i]['request']->getUri()->getPath());
            $this->assertSame($data['method'], $this->history[$i]['request']->getMethod());
            $this->assertSame($data['query'], $this->history[$i]['request']->getUri()->getQuery());
            $this->assertSame($data['requestBody'], (string) $this->history[$i]['request']->getBody());
        }
    }

    public function testCanBulkRequestWithPreviouslyAddedImage(): void
    {
        $this->mockHandler->append(
            // Response from adding image
            new Response(200, [], json_encode(['imageIdentifier' => 'imageId'])),

            // Bulk responses
            new Response(200),
            new Response(200),
            new Response(200),
        );

        $requests = new TableNode([
            ['path',                   'method', 'transformation', 'extension', 'access token'],
            ['previously added image', '',       'border',         '',          'yes'         ],
            ['previously added image', 'HEAD',   'thumbnail',      '',          'yes'         ],
            ['previously added image', '',       'strip',          'gif',       'yes'         ],
        ]);

        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey('publicKey', 'privateKey')
                ->addUserImageToImbo(FIXTURES_DIR . '/image1.png', 'user')
                ->requestPaths($requests),
        );

        $this->assertCount(
            4,
            $this->history,
            sprintf('Expected exactly 3 requests, got %d.', count($this->history)),
        );

        $this->assertSame('GET', $this->history[1]['request']->getMethod());
        $this->assertSame('/users/user/images/imageId', $this->history[1]['request']->getUri()->getPath());
        $this->assertSame('t%5B0%5D=border&publicKey=publicKey&accessToken=ec92fc446856c31b43facd62617f23c84d44e013ab3fff66db050291242f73e5', $this->history[1]['request']->getUri()->getQuery());

        $this->assertSame('HEAD', $this->history[2]['request']->getMethod());
        $this->assertSame('/users/user/images/imageId', $this->history[2]['request']->getUri()->getPath());
        $this->assertSame('t%5B0%5D=thumbnail&publicKey=publicKey&accessToken=2b2eb39e7b8542fe0ca68f9c7c005759b0c4e05eb036fb46358c7e00dc9df141', $this->history[2]['request']->getUri()->getQuery());

        $this->assertSame('GET', $this->history[3]['request']->getMethod());
        $this->assertSame('/users/user/images/imageId.gif', $this->history[3]['request']->getUri()->getPath());
        $this->assertSame('t%5B0%5D=strip&publicKey=publicKey&accessToken=5064e745998642e65c559c5ce5566f33926bcd18c041f10d93f602320c0d3c50', $this->history[3]['request']->getUri()->getQuery());
    }

    public function testCanBulkRequestWithSignedRequests(): void
    {
        $requests = new TableNode([
            ['path',   'method', 'sign request'],
            ['/path1', '',       'yes'         ],
            ['/path2', 'GET',    ''            ],
            ['/path3', 'HEAD',   'yes'         ],
        ]);
        $publicKey = 'publicKey';
        $privateKey = 'privateKey';

        $this->mockHandler->append(
            new Response(200),
            new Response(200),
            new Response(200),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey($publicKey, $privateKey)
                ->requestPaths($requests),
        );

        $this->assertCount(
            3,
            $this->history,
            sprintf('Expected exactly 1 request, got %d.', count($this->history)),
        );

        $this->assertSame('GET', $this->history[0]['request']->getMethod());
        $this->assertSame('/path1', $this->history[0]['request']->getUri()->getPath());
        $this->assertMatchesRegularExpression(
            '/^publicKey=publicKey&signature=[a-z0-9]{64}&timestamp=[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z$/',
            $this->history[0]['request']->getUri()->getQuery(),
        );

        $this->assertSame('GET', $this->history[1]['request']->getMethod());
        $this->assertSame('/path2', $this->history[1]['request']->getUri()->getPath());
        $this->assertEmpty($this->history[1]['request']->getUri()->getQuery());

        $this->assertSame('HEAD', $this->history[2]['request']->getMethod());
        $this->assertSame('/path3', $this->history[2]['request']->getUri()->getPath());
        $this->assertMatchesRegularExpression(
            '/^publicKey=publicKey&signature=[a-z0-9]{64}&timestamp=[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z$/',
            $this->history[2]['request']->getUri()->getQuery(),
        );
    }

    public function testCanBulkRequestWithMetadataOfPreviouslyAddedImage(): void
    {
        $this->mockHandler->append(
            // Response of adding image
            new Response(200, [], json_encode(['imageIdentifier' => 'imageId'])),

            // Response of adding metadata
            new Response(200, [], json_encode(['foo' => 'bar'])),

            // Responses to bulk requests
            new Response(200),
            new Response(200),
        );

        $requests = new TableNode([
            ['path',                               'method', 'access token'],
            ['metadata of previously added image', '',       'yes'         ],
            ['metadata of previously added image', 'HEAD',   'yes'         ],
        ]);

        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey('publicKey', 'privateKey')
                ->addUserImageToImbo(
                    FIXTURES_DIR . '/image1.png',
                    'user',
                    new PyStringNode(['{"foo": "bar"}'], 1),
                )
                ->requestPaths($requests),
        );

        $this->assertCount(
            4,
            $this->history,
            sprintf('Expected exactly 3 requests, got %d.', count($this->history)),
        );

        $this->assertSame('GET', $this->history[2]['request']->getMethod());
        $this->assertSame('/users/user/images/imageId/metadata', $this->history[2]['request']->getUri()->getPath());
        $this->assertSame('publicKey=publicKey&accessToken=78ed8225148fb3cc09d61ccd133831ef36e4bbd8ee757d6ff1378c65067d7775', $this->history[2]['request']->getUri()->getQuery());

        $this->assertSame('HEAD', $this->history[3]['request']->getMethod());
        $this->assertSame('/users/user/images/imageId/metadata', $this->history[3]['request']->getUri()->getPath());
        $this->assertSame('publicKey=publicKey&accessToken=78ed8225148fb3cc09d61ccd133831ef36e4bbd8ee757d6ff1378c65067d7775', $this->history[3]['request']->getUri()->getQuery());
    }

    public function testThrowsExceptionWhenTryingToRequestImageWithShortUrlWhenResponseHasInvalidBody(): void
    {
        $this->mockHandler->append(
            new Response(200),
            new Response(200),
        );

        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException(
            'Invalid response body in the current response instance',
        ));
        $this->context->requestImageUsingShortUrl();
    }

    public function testThrowsExceptionWhenTryingToRequestImageWithShortUrlWhenResponseBodyIsMissingId(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['foo' => 'bar'])),
            new Response(200),
        );

        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException(
            'Missing "id" from body: "{"foo":"bar"}".',
        ));
        $this->context->requestImageUsingShortUrl();
    }

    public function testCanRequestImageUsingShortUrlCreatedInPreviousRequest(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['id' => 'someId'])),
            new Response(200),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->requestImageUsingShortUrl(),
        );

        $this->assertCount(
            2,
            $this->history,
            sprintf('Expected exactly 2 requests, got %d.', count($this->history)),
        );

        $request = $this->history[1]['request'];

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/s/someId', $request->getUri()->getPath());
    }

    public function testThrowsExceptionWhenAssertingImboErrorWhenResponseIsNotAnError(): void
    {
        $this->makeRequest('/path');
        $this->expectExceptionObject(new InvalidArgumentException(
            'The status code of the last response is lower than 400, so it is not considered an error.',
        ));
        $this->context->assertImboError('some message');
    }

    public function testAssertingImboErrorMessageCanFailWhenMessageIsWrong(): void
    {
        $this->mockHandler->append(
            new Response(500, [], json_encode(['error' => [
                'message' => 'error message',
                'imboErrorCode' => 1,
            ]])),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected error message "foobar", got "error message".');
        $this->context->assertImboError('foobar');
    }

    public function testAssertingImboErrorMessageCanFailWhenCodeIsWrong(): void
    {
        $this->mockHandler->append(
            new Response(500, [], json_encode(['error' => [
                'message' => 'error message',
                'imboErrorCode' => 1,
            ]])),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected imbo error code "2", got "1".');
        $this->context->assertImboError('error message', 2);
    }

    public function testCanAssertImboErrorMessage(): void
    {
        $this->mockHandler->append(
            new Response(500, [], json_encode(['error' => [
                'message' => 'error message',
                'imboErrorCode' => 1,
            ]])),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImboError('error message', 1),
        );
    }

    public function testAssertingImageWidthCanFail(): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Incorrect image width, expected 123, got 599.',
        );
        $this->context->assertImageWidth(123);
    }

    public function testCanAssertImageWidth(): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImageWidth(599),
        );
    }

    #[DataProvider('getApproximateImageWidths')]
    public function testCanAssertApproximateImageWidth(string $approximateWidth): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImageWidth($approximateWidth),
        );
    }

    #[DataProvider('getApproximateImageWidthsForFailure')]
    public function testAssertingApproximateImageWidthCanFail(string $approximateWidth, string $exceptionMessage): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->context->assertImageWidth($approximateWidth);
    }

    public function testAssertingImageHeightCanFail(): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Incorrect image height, expected 123, got 417.',
        );
        $this->context->assertImageHeight(123);
    }

    public function testCanAssertImageHeight(): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImageHeight(417),
        );
    }

    #[DataProvider('getApproximateImageHeights')]
    public function testCanAssertApproximateImageHeight(string $approximateHeight): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImageHeight($approximateHeight),
        );
    }

    #[DataProvider('getApproximateImageHeightsForFailure')]
    public function testAssertingApproximateImageHeightCanFail(string $approximateHeight, string $exceptionMessage): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->context->assertImageHeight($approximateHeight);
    }

    #[DataProvider('getDataForImageDimensionAssertion')]
    public function testAssertingImageDimensionCanFail(string $imageData, string $dimension, string $exceptionMessage): void
    {
        $this->mockHandler->append(
            new Response(200, [], $imageData),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->context->assertImageDimension($dimension);
    }

    public function testThrowsExceptionWhenAssertingImageDimensionWhenInvalidDimensionString(): void
    {
        $this->mockHandler->append(
            new Response(200),
        );
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid dimension value: "123 x 456". Specify "<width>x<height>".',
        ));
        $this->context->assertImageDimension('123 x 456');
    }

    public function testCanAssertImageDimension(): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImageDimension('599x417'),
        );
    }

    #[DataProvider('getApproximateImageDimensions')]
    public function testCanAssertApproximateImageDimension(string $approximateDimension): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImageDimension($approximateDimension),
        );
    }

    #[DataProvider('getApproximateImageDimensionsForFailure')]
    public function testAssertingApproximateImageDimensionCanFail(string $approximateDimension, string $exceptionMessage): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->context->assertImageDimension($approximateDimension);
    }

    #[DataProvider('getDataForAssertingImagePixelInfoFailures')]
    public function testAssertingImagePixelColorCanFail(string $imageData, string $coordinate, string $color, string $exceptionMessage): void
    {
        $this->mockHandler->append(
            new Response(200, [], $imageData),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->context->assertImagePixelColor($coordinate, $color);
    }

    #[DataProvider('getDataForAssertingImagePixelInfo')]
    public function testCanAssertImagePixelColor(string $imageData, string $coordinate, string $color): void
    {
        $this->mockHandler->append(
            new Response(200, [], $imageData),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImagePixelColor($coordinate, $color),
        );
    }

    public function testThrowsExceptionWhenAssertingImagePixelColorWithInvalidCoordinate(): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/image1.png')),
        );

        $this->context->requestPath('/path');
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid coordinates: "1, 1". Format is "<w>x<h>", no spaces allowed.',
        ));
        $this->context->assertImagePixelColor('1, 1', 'ffffff');
    }

    #[DataProvider('getDataForAssertingImagePixelAlphaFailures')]
    public function testAssertingImagePixelAlphaCanFail(string $imageData, string $coordinate, float $alpha, string $exceptionMessage): void
    {
        $this->mockHandler->append(
            new Response(200, [], $imageData),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->context->assertImagePixelAlpha($coordinate, $alpha);
    }

    #[DataProvider('getDataForAssertingImagePixelAlpha')]
    public function testCanAssertImagePixelAlpha(string $imageData, string $coordinate, float $alpha): void
    {
        $this->mockHandler->append(
            new Response(200, [], $imageData),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImagePixelAlpha($coordinate, $alpha),
        );
    }

    public function testThrowsExceptionWhenAssertingImagePixelAlphaWithInvalidCoordinate(): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/transparency.png')),
        );

        $this->context->requestPath('/path');
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid coordinates: "1, 1". Format is "<w>x<h>", no spaces allowed.',
        ));
        $this->context->assertImagePixelAlpha('1, 1', 1);
    }

    public function testThrowsExceptionWhenAssertingThatAclRuleWithIdDoesNotExistWhenItDoesExist(): void
    {
        $this->mockHandler->append(new Response(200, [], '', '1.1', 'OK'));
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ACL rule "someId" with public key "publicKey" still exists. Expected "404 Access rule not found", got "200 OK".',
        );
        $this->context
            ->setPublicAndPrivateKey($this->publicKey, $this->privateKey)
            ->assertAclRuleWithIdDoesNotExist('publicKey', 'someId');
    }

    public function testCanAssertThatAclRuleWithIdDoesNotExist(): void
    {
        $this->mockHandler->append(new Response(404, [], '', '1.1', 'Access rule not found'));
        $this->context->setPublicAndPrivateKey($this->publicKey, $this->privateKey);

        $this->assertSame(
            $this->context,
            $this->context->assertAclRuleWithIdDoesNotExist('publicKey', 'someId'),
        );

        $this->assertCount(
            1,
            $this->history,
            sprintf('Expected exactly 1 request, got %d.', count($this->history)),
        );

        $this->assertSame(
            '/keys/publicKey/access/someId',
            $this->history[0]['request']->getUri()->getPath(),
        );
    }

    public function testThrowsExceptionWhenAssertingThatPublicKeyDoesNotExistWhenItDoes(): void
    {
        $this->mockHandler->append(new Response(200, [], '', '1.1', 'OK'));
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Public key "publicKey" still exists. Expected "404 Public key not found", got "200 OK".',
        );
        $this->context
            ->setPublicAndPrivateKey($this->publicKey, $this->privateKey)
            ->assertPublicKeyDoesNotExist('publicKey');
    }

    public function testCanAssertThatPublicKeyDoesNotExist(): void
    {
        $this->mockHandler->append(new Response(404, [], '', '1.1', 'Public key not found'));
        $this->context->setPublicAndPrivateKey($this->publicKey, $this->privateKey);
        $this->assertSame(
            $this->context,
            $this->context->assertPublicKeyDoesNotExist('publicKey'),
        );

        $this->assertCount(
            1,
            $this->history,
            sprintf('Expected exactly 1 request, got %d.', count($this->history)),
        );

        $this->assertSame('/keys/publicKey', $this->history[0]['request']->getUri()->getPath());
    }

    #[DataProvider('getCacheabilityData')]
    public function testCanAssertResponseCacheability(ResponseInterface $response, bool $expected, string $exceptionMessage = null): void
    {
        $this->mockHandler->append($response);
        $this->context->requestPath('/path');

        if ($exceptionMessage) {
            $this->expectException(Assert\InvalidArgumentException::class);
            $this->expectExceptionMessage($exceptionMessage);
            $this->context->assertCacheability($expected);
        } else {
            $this->assertSame(
                $this->context,
                $this->context->assertCacheability($expected),
            );
        }
    }

    public function testThrowsExceptionWhenAssertingMaxAgeAndResponseDoesNotHaveCacheControlHeader(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException(
            'Response does not have a cache-control header.',
        ));
        $this->context->assertMaxAge(123);
    }

    public function testThrowsExceptionWhenAssertingMaxAgeAndResponseCacheControlHeaderDoesNotHaveMaxAgeDirective(): void
    {
        $this->mockHandler->append(new Response(200, ['cache-control' => 'private']));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException(
            'Response cache-control header does not include a max-age directive: "private".',
        ));
        $this->context->assertMaxAge(123);
    }

    public function testCanAssertResponseMaxAge(): void
    {
        $this->mockHandler->append(new Response(200, ['cache-control' => 'private, max-age=600']));
        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertMaxAge(600),
        );
    }

    public function testAssertingResponseMaxAgeCanFail(): void
    {
        $this->mockHandler->append(new Response(200, ['cache-control' => 'private, max-age=456']));
        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The max-age directive in the cache-control header is not correct. Expected 123, got 456. Complete cache-control header: "private, max-age=456".',
        );
        $this->context->assertMaxAge(123);
    }

    public function testCanAssertThatASpecificCacheControlDirectiveExists(): void
    {
        $this->mockHandler->append(new Response(200, ['cache-control' => 'private, max-age=600, must-revalidate']));
        $this->context->requestPath('/path');
        foreach (['private', 'max-age', 'must-revalidate'] as $directive) {
            $this->assertSame(
                $this->context,
                $this->context->assertResponseHasCacheControlDirective($directive),
            );
        }
    }

    public function testThrowsExceptionWhenAssertingCacheControlHeaderDirectiveWhenResponseDoesNotHaveACacheControlHeader(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException(
            'Response does not have a cache-control header.',
        ));
        $this->context->assertResponseHasCacheControlDirective('must-revalidate');
    }

    public function testThrowsExceptionWhenAssertingThatACacheControlDirectiveExistsWhenItDoesNot(): void
    {
        $this->mockHandler->append(new Response(200, ['cache-control' => 'private, max-age=600']));
        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The cache-control header does not contain the "must-revalidate" directive. Complete cache-control header: "private, max-age=600".',
        );
        $this->context->assertResponseHasCacheControlDirective('must-revalidate');
    }

    public function testCanAssertThatASpecificCacheControlDirectiveDoesNotExists(): void
    {
        $this->mockHandler->append(new Response(200, ['cache-control' => 'private, max-age=600, must-revalidate']));
        $this->context->requestPath('/path');
        foreach (['public', 'no-cache', 'no-store'] as $directive) {
            $this->assertSame(
                $this->context,
                $this->context->assertResponseDoesNotHaveCacheControlDirective($directive),
            );
        }
    }

    public function testThrowsExceptionWhenAssertingResponseDoesNotHaveCacheControlHeaderDirectiveWhenResponseDoesNotHaveACacheControlHeader(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException(
            'Response does not have a cache-control header.',
        ));
        $this->context->assertResponseDoesNotHaveCacheControlDirective('must-revalidate');
    }

    public function testThrowsExceptionWhenAssertingThatACacheControlDirectiveDoesNotExistWhenItDoes(): void
    {
        $this->mockHandler->append(new Response(200, ['cache-control' => 'private, max-age=600, must-revalidate']));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException(
            'The cache-control header contains the "max-age" directive when it should not. Complete cache-control header: "private, max-age=600, must-revalidate".',
        ));
        $this->context->assertResponseDoesNotHaveCacheControlDirective('max-age');
    }

    public function testThrowsExceptionWhenAssertingTheLastResponseHeadersAndOnlyOneResponseExist(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new InvalidArgumentException(
            'Need to compare at least 2 responses, got 1.',
        ));
        $this->context->assertLastResponseHeaders(1, 'content-length');
    }

    public function testThrowsExceptionWhenAssertingTheLastResponseHeadersAndThereIsNotEnoughResponses(): void
    {
        $this->mockHandler->append(new Response(200), new Response(200));
        $this->context->requestPath('/path');
        $this->context->requestPath('/anotherPath');
        $this->expectExceptionObject(new InvalidArgumentException(
            'Not enough responses in the history. Need at least 4, there are currently 2.',
        ));
        $this->context->assertLastResponseHeaders(4, 'content-length');
    }

    public function testThrowsExceptionWhenAssertingLastResponseHeadersAndHeaderIsNotPresentInAllResponses(): void
    {
        $this->mockHandler->append(
            new Response(200, ['content-length' => '123']),
            new Response(200, ['content-length' => '123']),
            new Response(200),
        );
        $this->context->requestPath('/path1');
        $this->context->requestPath('/path2');
        $this->context->requestPath('/path3');
        $this->expectExceptionObject(new RuntimeException(
            'The "content-length" header is not present in all of the last 3 response headers.',
        ));
        $this->context->assertLastResponseHeaders(3, 'content-length');
    }

    public function testCanAssertLastResponesHeadersForUniqueness(): void
    {
        $this->mockHandler->append(
            new Response(200, ['content-length' => '123']),
            new Response(200, ['content-length' => '456']),
            new Response(200, ['content-length' => '789']),
        );
        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path1')
                ->requestPath('/path2')
                ->requestPath('/path3')
                ->assertLastResponseHeaders(3, 'content-length', true),
        );
    }

    public function testCanAssertLastResponesHeadersForNonUniqueness(): void
    {
        $this->mockHandler->append(
            new Response(200, ['content-length' => '123']),
            new Response(200, ['content-length' => '123']),
            new Response(200, ['content-length' => '123']),
        );
        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path1')
                ->requestPath('/path2')
                ->requestPath('/path3')
                ->assertLastResponseHeaders(3, 'content-length'),
        );
    }

    public function testCanAssertingLastResponesHeadersForUniquenessCanFail(): void
    {
        $this->mockHandler->append(
            new Response(200, ['content-length' => '123']),
            new Response(200, ['content-length' => '456']),
            new Response(200, ['content-length' => '456']),
        );
        $this->context->requestPath('/path1');
        $this->context->requestPath('/path2');
        $this->context->requestPath('/path3');

        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected 3 unique values, got 2. Values compared:',
        );

        $this->context->assertLastResponseHeaders(3, 'content-length', true);
    }

    public function testCanAssertingLastResponesHeadersForNonUniquenessCanFail(): void
    {
        $this->mockHandler->append(
            new Response(200, ['content-length' => '123']),
            new Response(200, ['content-length' => '123']),
            new Response(200, ['content-length' => '456']),
        );
        $this->context->requestPath('/path1');
        $this->context->requestPath('/path2');
        $this->context->requestPath('/path3');

        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected all values to be the same. Values compared:',
        );

        $this->context->assertLastResponseHeaders(3, 'content-length');
    }

    public function testCanAssertResponseBodySize(): void
    {
        $this->mockHandler->append(new Response(200, [], 'some string'));
        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertResponseBodySize(11),
        );
    }

    public function testAssertingResponseBodySizeCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], 'some string'));
        $this->context->requestPath('/path');

        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected response body size: 123, actual: 11.',
        );

        $this->context->assertResponseBodySize(123);
    }

    public function testThrowsExceptionWhenMatchingResponsesWithNoResponseKeyInTable(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Missing response column'));
        $this->context->assertLastResponsesMatch(new TableNode([['num'], ['3']]));
    }

    public function testThrowsExceptionWhenMatchingMoreResponsesThanWhatIsPresentInTheHistory(): void
    {
        $this->mockHandler->append(new Response(200), new Response(200));
        $this->context->requestPath('/path');
        $this->context->requestPath('/path');

        $this->expectExceptionObject(new RuntimeException(
            'Not enough transactions in the history. Needs at least 3, actual: 2.',
        ));

        $this->context->assertLastResponsesMatch(new TableNode([
            ['response'],
            ['3'],
        ]));
    }

    public function testThrowsExceptionWhenMatchingResponsesAndARowIsMissingResponseNumber(): void
    {
        $this->mockHandler->append(new Response(200), new Response(200));
        $this->context->requestPath('/path');
        $this->context->requestPath('/path');

        $this->expectExceptionObject(new InvalidArgumentException(
            'Each row must refer to a response by using the "response" column.',
        ));

        $this->context->assertLastResponsesMatch(new TableNode([
            ['response'],
            ['1'],
            [''],
        ]));
    }

    public function testThrowsExceptionWhenMatchingResponsesAndAnInvalidColumnIsUsed(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/path');

        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid column name: "foobar".',
        ));

        $this->context->assertLastResponsesMatch(new TableNode([
            ['response', 'foobar'],
            ['1',        'baz'   ],
        ]));
    }

    #[DataProvider('getDataForMatchingSeveralResponses')]
    public function testCanMatchResponses(array $responses, TableNode $match): void
    {
        $this->mockHandler->append(...$responses);

        for ($i = 0; $i < count($responses); $i++) {
            $this->context->requestPath('/path');
        }

        $this->assertSame(
            $this->context,
            $this->context->assertLastResponsesMatch($match),
        );
    }

    #[DataProvider('getDataForMatchingSeveralResponsesWhenFailing')]
    public function testAssertLastResponsesMatchCanFail(array $responses, TableNode $match, string $exceptionMessage): void
    {
        $this->mockHandler->append(...$responses);

        for ($i = 0; $i < count($responses); $i++) {
            $this->context->requestPath('/path');
        }

        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->context->assertLastResponsesMatch($match);
    }

    public function testAssertLastResponsesMatchCanFailForJsonMatching(): void
    {
        $this->mockHandler->append(
            new Response(200, [], '{"foo":"bar"}'),
            new Response(200, [], '{"bar":"foo"}'),
        );

        $this->context->requestPath('/path');
        $this->context->requestPath('/path');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Incorrect response body for request 2, expected "{"bar":"foobar"}", got: "{"bar":"foo"}".');

        $this->context->assertLastResponsesMatch(new TableNode([
            ['response', 'body is'         ],
            ['1',        '{"foo":"bar"}'   ],
            ['2',        '{"bar":"foobar"}'],
        ]));
    }

    public function testThrowsExceptionWhenAssertingImagePropertiesAndResponseDoesNotContainAValidImage(): void
    {
        $this->mockHandler->append(new Response(200, [], 'foobar'));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new RuntimeException('Imagick could not read response body:'));
        $this->context->assertImageProperties('prefix');
    }

    public function testCanAssertThatImageDoesNotHaveAnyPropertiesWithASpecificPrefix(): void
    {
        $this->mockHandler->append(new Response(200, [], file_get_contents(FIXTURES_DIR . '/image.png')));
        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertImageProperties('foobar'),
        );
    }

    public function testAssertingThatImageDoesNotHaveAnyPropertiesWithASpecificPrefixCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], file_get_contents(FIXTURES_DIR . '/image.png')));
        $this->context->requestPath('/path');
        $this->expectExceptionObject(new AssertionFailedException(
            'Image properties have not been properly stripped. Did not expect properties that starts with "png", found: "png:',
        ));
        $this->context->assertImageProperties('png');
    }

    #[DataProvider('getSuiteSettings')]
    public function testSetupAdaptersThrowsExceptionOnInvalidClassNames(array $settings, string $expectedExceptionMessage): void
    {
        $suite = $this->createConfiguredMock(Suite::class, ['getSettings' => $settings]);
        $environment = $this->createConfiguredMock(Environment::class, ['getSuite' => $suite]);

        $this->expectExceptionObject(new InvalidArgumentException($expectedExceptionMessage));
        FeatureContext::setUpAdapters(new BeforeScenarioScope(
            $environment,
            $this->createMock(FeatureNode::class),
            $this->createMock(ScenarioInterface::class),
        ));
    }

    #[DataProvider('getCoordsAndColorsForFailures')]
    public function testAssertApproximatePixelColorCanFail(string $coordinates, string $color, string $exceptionMessage): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/colors.png')),
        );

        $this->context->requestPath('/path');
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->context->assertApproximateImagePixelColor($coordinates, $color);
    }

    #[DataProvider('getCoordsAndColors')]
    public function testAssertApproximatePixelColor(string $coordinates, string $color): void
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents(FIXTURES_DIR . '/colors.png')),
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->requestPath('/path')
                ->assertApproximateImagePixelColor($coordinates, $color),
        );
    }

    /**
     * @return array<array<string>>
     */
    public static function getImboConfigFiles(): array
    {
        return array_map(
            fn (string $file): array => [basename($file)],
            glob(__DIR__ . '/../../features/bootstrap/imbo-configs/*.php'),
        );
    }

    /**
     * @return array<array{adapter:string,header:string}>
     */
    public static function getAdaptersForFailure(): array
    {
        return [
            ['adapter' => 'database', 'header' => 'X-Imbo-Status-Database-Failure'],
            ['adapter' => 'storage', 'header' => 'X-Imbo-Status-Storage-Failure'],
        ];
    }

    /**
     * @return array<array{path:string,expectedUrl:string}>
     */
    public static function getDataForAccessTokens(): array
    {
        return [
            'path with no query params' => [
                'path' => '/path',
                'expectedUrl' => 'http://localhost:8080/path?publicKey=publicKey&accessToken=582386896ffacd2c34a39476f0fa71ac9e6b22f079482ea7ee687e15826b08ef',
            ],
            'path with query params' => [
                'path' => '/path?foo=bar',
                'expectedUrl' => 'http://localhost:8080/path?foo=bar&publicKey=publicKey&accessToken=67bd5be81cd63180d9dba642e22fc6c9940c4313913dee5db692b0eb86aabb6b',
            ],
            'path with problematic query params' => [
                'path' => '/path?bar=foo&publicKey=foobar&accessToken=sometoken',
                'expectedUrl' => 'http://localhost:8080/path?bar=foo&publicKey=publicKey&accessToken=f43f2db7f8c34c521456c4bb6f926812b39c3081a7a3d295ca14ccdc38926f2c',
            ],
        ];
    }

    /**
     * @return array<array{publicKey:string,privateKey:string,authMethod:string,uriRegExp:string,headers:array<string,string>}>
     */
    public static function getAuthDetails(): array
    {
        return [
            'access-token' => [
                'publicKey' => 'publicKey',
                'privateKey' => 'privateKey',
                'authMethod' => 'access-token',
                'uriRegExp' => '|^http://localhost:8080/path\?publicKey=publicKey&accessToken=582386896ffacd2c34a39476f0fa71ac9e6b22f079482ea7ee687e15826b08ef$|',
                'headers' => [],
            ],
            'access-token #2' => [
                'publicKey' => 'key',
                'privateKey' => 'secret',
                'authMethod' => 'access-token',
                'uriRegExp' => '|^http://localhost:8080/path\?publicKey=key&accessToken=dd4217a681cf8abdcecdc68cf49630df1e57dc733735e902b8a69859e50797a8$|',
                'headers' => [],
            ],
            'signature' => [
                'publicKey' => 'publicKey',
                'privateKey' => 'privateKey',
                'authMethod' => 'signature',
                'uriRegExp' => '|^http://localhost:8080/path\?publicKey=publicKey&signature=[a-z0-9]{64}&timestamp=[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z$|',
                'headers' => [],
            ],
            'signature #2' => [
                'publicKey' => 'key',
                'privateKey' => 'secret',
                'authMethod' => 'signature',
                'uriRegExp' => '|^http://localhost:8080/path\?publicKey=key&signature=[a-z0-9]{64}&timestamp=[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z$|',
                'headers' => [],
            ],
            'signature (headers)' => [
                'publicKey' => 'publicKey',
                'privateKey' => 'privateKey',
                'authMethod' => 'signature (headers)',
                'uriRegExp' => '|^http://localhost:8080/path$|',
                'headers' => [
                    'X-Imbo-PublicKey' => '/^publicKey$/',
                    'X-Imbo-Authenticate-Signature' => '/^[a-z0-9]{64}$/',
                    'X-Imbo-Authenticate-Timestamp' => '/^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z$/',
                ],
            ],
            'signature (headers) #2' => [
                'publicKey' => 'key',
                'privateKey' => 'secret',
                'authMethod' => 'signature (headers)',
                'uriRegExp' => '|^http://localhost:8080/path$|',
                'headers' => [
                    'X-Imbo-PublicKey' => '/^key$/',
                    'X-Imbo-Authenticate-Signature' => '/^[a-z0-9]{64}$/',
                    'X-Imbo-Authenticate-Timestamp' => '/^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z$/',
                ],
            ],
        ];
    }

    /**
     * @return array<array{params:array<array{name:string,value:string}>,uri:string}>
     */
    public static function getRequestQueryParams(): array
    {
        return [
            'single key / value' => [
                'params' => [
                    ['name' => 'key', 'value' => 'value'],
                ],
                'uri' => 'http://localhost:8080/path?key=value',
            ],
            'multiple key / value' => [
                'params' => [
                    ['name' => 'foo', 'value' => 'bar'],
                    ['name' => 'bar', 'value' => 'foo'],
                    ['name' => 'foobar', 'value' => 'barfoo'],
                ],
                'uri' => 'http://localhost:8080/path?foo=bar&bar=foo&foobar=barfoo',
            ],
            'array values' => [
                'params' => [
                    ['name' => 't[]', 'value' => 'border'],
                    ['name' => 't[]', 'value' => 'thumb'],
                ],
                'uri' => 'http://localhost:8080/path?t%5B0%5D=border&t%5B1%5D=thumb',
            ],
            'mixed values' => [
                'params' => [
                    ['name' => 'foo', 'value' => 'bar'],
                    ['name' => 't[]', 'value' => 'border'],
                    ['name' => 'bar', 'value' => 'foo'],
                    ['name' => 't[]', 'value' => 'thumb'],
                ],
                'uri' => 'http://localhost:8080/path?foo=bar&t%5B0%5D=border&t%5B1%5D=thumb&bar=foo',
            ],
        ];
    }

    /**
     * @return array<array{image:string,user:string,imageIdentifier:string,params:array<string,string>}>
     */
    public static function getShortUrlParams(): array
    {
        return [
            [
                'image' => FIXTURES_DIR . '/image1.png',
                'user' => 'user',
                'imageIdentifier' => 'fc7d2d06993047a0b5056e8fac4462a2',
                'params' => [
                    'user' => 'user',
                ],
            ],
            [
                'image' => FIXTURES_DIR . '/image2.png',
                'user' => 'user',
                'imageIdentifier' => 'b914b28f4d5faa516e2049b9a6a2577c',
                'params' => [
                    'user' => 'user',
                    'extension' => 'gif',
                ],
            ],
            [
                'image' => FIXTURES_DIR . '/image3.png',
                'user' => 'user',
                'imageIdentifier' => '1d5b88aec8a3e1c4c57071307b2dae3a',
                'params' => [
                    'user' => 'user',
                    'query' => 't[]=thumbnail:width=45,height=55&t[]=desaturate',
                ],
            ],
            [
                'image' => FIXTURES_DIR . '/image4.png',
                'user' => 'user',
                'imageIdentifier' => 'a501051db16e3cbf88ea50bfb0138a47',
                'params' => [
                    'user' => 'user',
                    'extension' => 'jpg',
                    'query' => 't[]=thumbnail:width=45,height=55&t[]=desaturate',
                ],
            ],
        ];
    }

    /**
     * @return array<array{image:string,imageIdentifier:string,params:?string,uri:string}>
     */
    public static function getDataForWatermarkImages(): array
    {
        return [
            'no params' => [
                'image' => FIXTURES_DIR . '/image1.png',
                'imageIdentifier' => 'someId',
                'params' => null,
                'uri' => 'http://localhost:8080/path?t%5B0%5D=watermark%3Aimg%3DsomeId',
            ],
            'with params' => [
                'image' => FIXTURES_DIR . '/image1.png',
                'imageIdentifier' => 'someId',
                'params' => 'x=10,y=5,position=bottom-right,width=20,height=20',
                'uri' => 'http://localhost:8080/path?t%5B0%5D=watermark%3Aimg%3DsomeId%2Cx%3D10%2Cy%3D5%2Cposition%3Dbottom-right%2Cwidth%3D20%2Cheight%3D20',
            ],
        ];
    }

    /**
     * @return array<array{imageIdentifier:string,image:string,method:string}>
     */
    public static function getDataForRequestingPreviouslyAddedImage(): array
    {
        return [
            'HTTP GET' => [
                'imageIdentifier' => 'imageId',
                'image' => FIXTURES_DIR . '/image1.png',
                'method' => 'GET',
            ],
            'HTTP DELETE' => [
                'imageIdentifier' => 'imageId',
                'image' => FIXTURES_DIR . '/image1.png',
                'method' => 'DELETE',
            ],
        ];
    }

    /**
     * @return array<array{imageIdentifier:string,image:string,method:string,extension:string}>
     */
    public static function getDataForRequestingPreviouslyAddedImageWithExtension(): array
    {
        return [
            [
                'imageIdentifier' => 'imageId',
                'image' => FIXTURES_DIR . '/image1.png',
                'method' => 'HEAD',
                'extension' => 'png',
            ],
            [
                'imageIdentifier' => 'imageId',
                'image' => FIXTURES_DIR . '/image1.png',
                'method' => 'GET',
                'extension' => 'jpg',
            ],
        ];
    }

    /**
     * @return array<array{originalMethod:string,method:?string,expectedUrl:string,publicKey:?string,privateKey:?string}>
     */
    public static function getDataForReplayingRequests(): array
    {
        return [
            'use original method' => [
                'originalMethod' => 'GET',
                'method' => null,
                'expectedUrl' => 'http://localhost:8080/path',
                'publicKey' => null,
                'privateKey' => null,
            ],
            'specify custom method' => [
                'originalMethod' => 'GET',
                'method' => 'HEAD',
                'expectedUrl' => 'http://localhost:8080/path',
                'publicKey' => null,
                'privateKey' => null,
            ],
            'specify custom method that the same as the original' => [
                'originalMethod' => 'DELETE',
                'method' => 'DELETE',
                'expectedUrl' => 'http://localhost:8080/path',
                'publicKey' => null,
                'privateKey' => null,
            ],
            'specify custom method and append access token' => [
                'originalMethod' => 'DELETE',
                'method' => 'DELETE',
                'expectedUrl' => 'http://localhost:8080/path?publicKey=key&accessToken=dd4217a681cf8abdcecdc68cf49630df1e57dc733735e902b8a69859e50797a8',
                'publicKey' => 'key',
                'privateKey' => 'secret',
            ],
        ];
    }

    /**
     * @return array<array{imageIdentifier:string,image:string,method:string,metadata:array<string,string>}>
     */
    public static function getDataForRequestingMetadataOfPreviouslyAddedImage(): array
    {
        return [
            'no metadata' => [
                'imageIdentifier' => 'imageId',
                'image' => FIXTURES_DIR . '/image1.png',
                'method' => 'GET',
                'metadata' => [],
            ],
            'with metadata and custom method' => [
                'imageIdentifier' => 'imageId',
                'image' => FIXTURES_DIR . '/image1.png',
                'method' => 'HEAD',
                'metadata' => ['key' => 'value'],
            ],
        ];
    }

    /**
     * @return array<array{image:string,imageIdentifier:string,extension:?string,method:string,expectedPath:string}>
     */
    public static function getDataForRequestingImageWithLocalPath(): array
    {
        return [
            'default values' => [
                'image' => FIXTURES_DIR . '/image1.png',
                'imageIdentifier' => 'imageId',
                'extension' => null,
                'method' => 'GET',
                'expectedPath' => '/users/user/images/imageId',
            ],
            'custom extension and method' => [
                'image' => FIXTURES_DIR . '/image1.png',
                'imageIdentifier' => 'imageId',
                'extension' => 'gif',
                'method' => 'HEAD',
                'expectedPath' => '/users/user/images/imageId.gif',
            ],
        ];
    }

    /**
     * @return array<array{table:TableNode,requests:array<int,array{path:string,method:string,query:string,requestBody:string}>}>
     */
    public static function getDataForBulkRequests(): array
    {
        return [
            'single request with no options' => [
                'table' => new TableNode([
                    ['path'],
                    ['/path'],
                ]),
                'requests' => [
                    [
                        'path' => '/path',
                        'method' => 'GET',
                        'query' => '',
                        'requestBody' => '',
                    ],
                ],
            ],
            'append access token' => [
                'table' => new TableNode([
                    ['path',  'access token'],
                    ['/path', 'yes'],
                ]),
                'requests' => [
                    [
                        'path' => '/path',
                        'method' => 'GET',
                        'query' => 'publicKey=publicKey&accessToken=582386896ffacd2c34a39476f0fa71ac9e6b22f079482ea7ee687e15826b08ef',
                        'requestBody' => '',
                    ],
                ],
            ],
            'add transformation' => [
                'table' => new TableNode([
                    ['path',  'transformation'],
                    ['/path', 'border'],
                ]),
                'requests' => [
                    [
                        'path' => '/path',
                        'method' => 'GET',
                        'query' => 't%5B0%5D=border',
                        'requestBody' => '',
                    ],
                ],
            ],
            'set request body' => [
                'table' => new TableNode([
                    ['path',  'request body'],
                    ['/path', 'some data'],
                ]),
                'requests' => [
                    [
                        'path' => '/path',
                        'method' => 'GET',
                        'query' => '',
                        'requestBody' => 'some data',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function getApproximateImageWidths(): array
    {
        return [
            ['598±1'],
            ['600±1'],
            ['589±10'],
            ['609±10'],
        ];
    }

    /**
     * @return array<array{approximateWidth:string,exceptionMessage:string}>
     */
    public static function getApproximateImageWidthsForFailure(): array
    {
        return [
            [
                'approximateWidth' => '597±1',
                'exceptionMessage' => 'Expected image width to be between 596 and 598 inclusive, got 599.',
            ],
            [
                'approximateWidth' => '601±1',
                'exceptionMessage' => 'Expected image width to be between 600 and 602 inclusive, got 599.',
            ],
            [
                'approximateWidth' => '588±10',
                'exceptionMessage' => 'Expected image width to be between 578 and 598 inclusive, got 599.',
            ],
            [
                'approximateWidth' => '610±10',
                'exceptionMessage' => 'Expected image width to be between 600 and 620 inclusive, got 599.',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function getApproximateImageHeights(): array
    {
        return [
            ['416±1'],
            ['418±1'],
            ['407±10'],
            ['427±10'],
        ];
    }

    /**
     * @return array<array{approximateHeight:string,exceptionMessage:string}>
     */
    public static function getApproximateImageHeightsForFailure(): array
    {
        return [
            [
                'approximateHeight' => '415±1',
                'exceptionMessage' => 'Expected image height to be between 414 and 416 inclusive, got 417.',
            ],
            [
                'approximateHeight' => '419±1',
                'exceptionMessage' => 'Expected image height to be between 418 and 420 inclusive, got 417.',
            ],
            [
                'approximateHeight' => '406±10',
                'exceptionMessage' => 'Expected image height to be between 396 and 416 inclusive, got 417.',
            ],
            [
                'approximateHeight' => '428±10',
                'exceptionMessage' => 'Expected image height to be between 418 and 438 inclusive, got 417.',
            ],
        ];
    }

    /**
     * @return array<array{imageData:string,dimension:string,exceptionMessage:string}>
     */
    public static function getDataForImageDimensionAssertion(): array
    {
        $image1 = file_get_contents(FIXTURES_DIR . '/image1.png');
        $image2 = file_get_contents(FIXTURES_DIR . '/image2.png');

        return [
            [
                'imageData' => $image1,
                'dimension' => '123x456',
                'exceptionMessage' => 'Incorrect image width, expected 123, got 599.',
            ],
            [
                'imageData' => $image1,
                'dimension' => '599x456',
                'exceptionMessage' => 'Incorrect image height, expected 456, got 417.',
            ],
            [
                'imageData' => $image2,
                'dimension' => '123x456',
                'exceptionMessage' => 'Incorrect image width, expected 123, got 539.',
            ],
            [
                'imageData' => $image2,
                'dimension' => '539x123',
                'exceptionMessage' => 'Incorrect image height, expected 123, got 375.',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function getApproximateImageDimensions(): array
    {
        return [
            ['598±1x415±2'],
            ['600±1x419±2'],
            ['589±10x400±17'],
            ['609±10x434±17'],
        ];
    }

    /**
     * @return array<array{approximateDimension:string,exceptionMessage:string}>
     */
    public static function getApproximateImageDimensionsForFailure(): array
    {
        return [
            [
                'approximateDimension' => '597±1x416±1',
                'exceptionMessage' => 'Expected image width to be between 596 and 598 inclusive, got 599.',
            ],
            [
                'approximateDimension' => '599x414±2',
                'exceptionMessage' => 'Expected image height to be between 412 and 416 inclusive, got 417.',
            ],
        ];
    }

    /**
     * @return array<array{imageData:string,coordinate:string,color:string,exceptionMessage:string}>
     */
    public static function getDataForAssertingImagePixelInfoFailures(): array
    {
        $image = file_get_contents(FIXTURES_DIR . '/image1.png');

        return [
            [
                'imageData' => $image,
                'coordinate' => '1,1',
                'color' => '000000',
                'exceptionMessage' => 'Incorrect color at coordinate "1,1", expected "000000", got "ffffff".',
            ],
            [
                'imageData' => $image,
                'coordinate' => '247,32',
                'color' => '000000',
                'exceptionMessage' => 'Incorrect color at coordinate "247,32", expected "000000", got "e8e7e6".',
            ],
            [
                'imageData' => $image,
                'coordinate' => '275,150',
                'color' => 'ffffff',
                'exceptionMessage' => 'Incorrect color at coordinate "275,150", expected "ffffff", got "000000".',
            ],
        ];
    }

    /**
     * @return array<array{imageData:string,coordinate:string,color:string}>
     */
    public static function getDataForAssertingImagePixelInfo(): array
    {
        $image = file_get_contents(FIXTURES_DIR . '/image1.png');

        return [
            [
                'imageData' => $image,
                'coordinate' => '1,1',
                'color' => 'ffffff',
            ],
            [
                'imageData' => $image,
                'coordinate' => '247,32',
                'color' => 'e8e7e6',
            ],
            [
                'imageData' => $image,
                'coordinate' => '275,150',
                'color' => '000000',
            ],
        ];
    }

    /**
     * @return array<array{imageData:string,coordinate:string,alpha:float,exceptionMessage:string}>
     */
    public static function getDataForAssertingImagePixelAlphaFailures(): array
    {
        $image = file_get_contents(FIXTURES_DIR . '/transparency.png');

        return [
            [
                'imageData' => $image,
                'coordinate' => '448,192',
                'alpha' => 0,
                'exceptionMessage' => sprintf('Incorrect alpha value at coordinate "448,192", expected "%f", got "%f".', 0, 1),
            ],
            [
                'imageData' => $image,
                'coordinate' => '192,64',
                'alpha' => 1,
                'exceptionMessage' => sprintf('Incorrect alpha value at coordinate "192,64", expected "%f", got "%f".', 1, 0),
            ],
        ];
    }

    /**
     * @return array<array{imageData:string,coordinate:string,alpha:float}>
     */
    public static function getDataForAssertingImagePixelAlpha(): array
    {
        $image = file_get_contents(FIXTURES_DIR . '/transparency.png');

        return [
            [
                'imageData' => $image,
                'coordinate' => '448,192',
                'alpha' => 1,
            ],
            [
                'imageData' => $image,
                'coordinate' => '192,64',
                'alpha' => 0,
            ],
        ];
    }

    /**
     * @return array<array{response:Response,expected:bool,exceptionMessage?:string}>
     */
    public static function getCacheabilityData(): array
    {
        return [
            'cacheable, expect cacheable' => [
                'response' => new Response(200),
                'expected' => true,
            ],
            'not cacheable, expect not cacheable' => [
                'response' => new Response(400),
                'expected' => false,
            ],
            'cacheable, expect not cacheable' => [
                'response' => new Response(200),
                'expected' => false,
                'exceptionMessage' => 'Response was not supposed to be cacheable, but it is.',
            ],
            'not cacheable, expect cacheable' => [
                'response' => new Response(400),
                'expected' => true,
                'exceptionMessage' => 'Response was supposed to be cacheble, but it\'s not.',
            ],
            'not cacheable (no-store)' => [
                'response' => new Response(200, ['cache-control' => 'no-store']),
                'expected' => false,
            ],
            'not cacheable (private)' => [
                'response' => new Response(200, ['cache-control' => 'private']),
                'expected' => false,
            ],
        ];
    }

    /**
     * @return array<array{responses:array<Response>,match:TableNode}>
     */
    public static function getDataForMatchingSeveralResponses(): array
    {
        return [
            'status line' => [
                'responses' => [
                    new Response(200),
                    new Response(204),
                    new Response(404),
                    new Response(500),
                ],
                'match' => new TableNode([
                    ['response', 'status line'              ],
                    ['1',        '200 OK'                   ],
                    ['2',        '204 No Content'           ],
                    ['3',        '404 Not Found'            ],
                    ['4',        '500 Internal Server Error'],
                ]),
            ],
            'headers' => [
                'responses' => [
                    new Response(200, [
                        'content-type' => 'application/json',
                        'content-length' => '13',
                    ], '{"foo":"bar"}'),
                    new Response(200, [
                        'x-imbo-foo' => 'bar',
                    ], '{"foo":"bar"}'),
                ],
                'match' => new TableNode([
                    ['response', 'header name',    'header value'    ],
                    ['1',        'content-type',   'application/json'],
                    ['1',        'content-length', '13'              ],
                    ['2',        'x-imbo-foo',     'bar'             ],
                ]),
            ],
            'checksum' => [
                'responses' => [
                    new Response(200, [], '{"foo":"bar"}'),
                    new Response(200, [], '{"bar":"foo"}'),
                ],
                'match' => new TableNode([
                    ['response', 'checksum'                        ],
                    ['1',        '9bb58f26192e4ba00f01e2e7b136bbd8'],
                    ['2',        'e561e07998cff8eca9f3acc8a2fdb12f'],
                ]),
            ],
            'image width / height' => [
                'responses' => [
                    new Response(200, [], file_get_contents(FIXTURES_DIR . '/1024x256.png')),
                    new Response(200, [], file_get_contents(FIXTURES_DIR . '/256x1024.png')),
                ],
                'match' => new TableNode([
                    ['response', 'image width', 'image height'],
                    ['1',        1024,          256           ],
                    ['2',        256,           1024          ],
                ]),
            ],
            'body is' => [
                'responses' => [
                    new Response(200, [], '{"foo":"bar"}'),
                    new Response(200, [], '{"bar":"foo"}'),
                ],
                'match' => new TableNode([
                    ['response', 'body is'      ],
                    ['1',        '{"foo":"bar"}'],
                    ['2',        '{"bar":"foo"}'],
                ]),
            ],
        ];
    }

    /**
     * @return array<array{responses:array<Response>,match:TableNode,exceptionMessage:string}>
     */
    public static function getDataForMatchingSeveralResponsesWhenFailing(): array
    {
        return [
            'status line' => [
                'responses' => [
                    new Response(200),
                    new Response(201),
                ],
                'match' => new TableNode([
                    ['response', 'status line'   ],
                    ['1',        '200 OK'        ],
                    ['2',        '204 No Content'],
                ]),
                'exceptionMessage' => 'Incorrect status line in response 2, expected "204 No Content", got: "201 Created".',
            ],
            'headers' => [
                'responses' => [
                    new Response(200, [
                        'content-type' => 'application/json',
                    ], '{"foo":"bar"}'),
                    new Response(200, [
                        'x-imbo-foo' => 'bar',
                    ], '{"foo":"bar"}'),
                ],
                'match' => new TableNode([
                    ['response', 'header name',    'header value'    ],
                    ['1',        'content-type',   'application/json'],
                    ['2',        'x-imbo-foo',     'foobar'          ],
                ]),
                'exceptionMessage' => 'Incorrect "x-imbo-foo" header value in response 2, expected "foobar", got: "bar".',
            ],
            'checksum' => [
                'responses' => [
                    new Response(200, [], '{"foo":"bar"}'),
                    new Response(200, [], '{"bar":"foo"}'),
                ],
                'match' => new TableNode([
                    ['response', 'checksum'                        ],
                    ['1',        '9bb58f26192e4ba00f01e2e7b136bbd8'],
                    ['2',        '9bb58f26192e4ba00f01e2e7b136bbd8'],
                ]),
                'exceptionMessage' => 'Incorrect checksum in response 2, expected "9bb58f26192e4ba00f01e2e7b136bbd8", got: "e561e07998cff8eca9f3acc8a2fdb12f".',
            ],
            'image width / height (failure on width)' => [
                'responses' => [
                    new Response(200, [], file_get_contents(FIXTURES_DIR . '/1024x256.png')),
                    new Response(200, [], file_get_contents(FIXTURES_DIR . '/256x1024.png')),
                ],
                'match' => new TableNode([
                    ['response', 'image width', 'image height'],
                    ['1',        1024,          256           ],
                    ['2',        255,           1024          ],
                ]),
                'exceptionMessage' => 'Expected image in response 2 to be 255 pixel(s) wide, actual: 256.',
            ],
            'image width / height (failure on height)' => [
                'responses' => [
                    new Response(200, [], file_get_contents(FIXTURES_DIR . '/1024x256.png')),
                    new Response(200, [], file_get_contents(FIXTURES_DIR . '/256x1024.png')),
                ],
                'match' => new TableNode([
                    ['response', 'image width', 'image height'],
                    ['1',        1024,          256           ],
                    ['2',        256,           1023          ],
                ]),
                'exceptionMessage' => 'Expected image in response 2 to be 1023 pixel(s) high, actual: 1024.',
            ],
        ];
    }

    /**
     * @return array<array{settings:array,expectedExceptionMessage:string}>
     */
    public static function getSuiteSettings(): array
    {
        return [
            'invalid database' => [
                'settings' => [
                    'database' => 'Foobar',
                    'storage' => 'GridFS',
                ],
                'expectedExceptionMessage' => 'Database test class "Imbo\Behat\DatabaseTest\Foobar" does not exist.',
            ],
            'invalid storage' => [
                'settings' => [
                    'database' => 'MongoDB',
                    'storage' => 'Foobar',
                ],
                'expectedExceptionMessage' => 'Storage test class "Imbo\Behat\StorageTest\Foobar" does not exist.',
            ],
        ];
    }

    /**
     * @return array<array{coordinates:string,color:string}>
     */
    public static function getCoordsAndColors(): array
    {
        return [
            ['coordinates' => '35,35', 'color' => '#008000'],
            ['coordinates' => '35,35', 'color' => '#008100'],
            ['coordinates' => '35,35', 'color' => '#008200'],
            ['coordinates' => '35,35', 'color' => '#008300'],
        ];
    }

    /**
     * @return array<array{coordinates:string,color:string,exceptionMessage:string}>
     */
    public static function getCoordsAndColorsForFailures(): array
    {
        return [
            ['coordinates' => '22,22', 'color' => '#333333', 'exceptionMessage' => 'Color approximation failed for red color, expected "49 - 53", got "255".'],
            ['coordinates' => '22,22', 'color' => '#ff3333', 'exceptionMessage' => 'Color approximation failed for green color, expected "49 - 53", got "0".'],
            ['coordinates' => '22,22', 'color' => '#ff0033', 'exceptionMessage' => 'Color approximation failed for blue color, expected "49 - 53", got "255".'],
        ];
    }
}
