<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest;

use FeatureContext;
use Micheh\Cache\CacheUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase;
use Behat\Gherkin\Node\PyStringNode;

/**
 * @coversDefaultClass FeatureContext
 * @group unit
 * @group behat
 */
class FeatureContextTest extends PHPUnit_Framework_TestCase {
    /**
     * @var FeatureContext
     */
    private $context;

    /**
     * @var CacheUtil
     */
    private $cacheUtil;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $history;

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var string
     */
    private $baseUri = 'http://localhost:8080';

    /**
     * @var string
     */
    private $publicKey = 'publicKey';

    /**
     * @var string
     */
    private $privateKey = 'privateKey';

    /**
     * Set up the feature context
     */
    public function setUp() {
        $this->history = [];

        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->history));
        $this->client = new Client([
            'handler' => $this->handlerStack,
            'base_uri' => $this->baseUri,
        ]);
        $this->cacheUtil = $this->createMock('Micheh\Cache\CacheUtil');

        $this->context = new FeatureContext($this->cacheUtil);
        $this->context->setClient($this->client);
    }

    /**
     * Convenience method to make a single request and return the request instance
     *
     * @param string $path
     * @return Request
     */
    private function makeRequest($path = '/somepath') {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath($path);

        return $this->history[count($this->history) - 1]['request'];
    }

    /**
     * @covers ::setClient
     */
    public function testCanSetAnApiClient() {
        $handlerStack = $this->createMock('GuzzleHttp\HandlerStack');
        $handlerStack
            ->expects($this->once())
            ->method('push')
            ->with($this->isInstanceOf('Closure'), $this->isType('string'));

        $client = $this->createMock('GuzzleHttp\ClientInterface');
        $client
            ->expects($this->at(0))
            ->method('getConfig')
            ->with('handler')
            ->willReturn($handlerStack);
        $client
            ->expects($this->at(1))
            ->method('getConfig')
            ->with('base_uri')
            ->willReturn('http://localhost:8080');

        $context = new FeatureContext();
        $this->assertSame($context, $context->setClient($client));
    }

    /**
     * @covers ::setArrayContainsComparator
     */
    public function testAttachesComparatorFunctions() {
        $comparator = $this->createMock('Imbo\BehatApiExtension\ArrayContainsComparator');
        $comparator
            ->expects($this->once())
            ->method('addFunction')
            ->with($this->isType('string'), $this->isType('array'));
        $this->assertSame($this->context, $this->context->setArrayContainsComparator($comparator));
    }

    /**
     * @covers ::setRequestHeader
     */
    public function testCanSetRequestHeader() {
        $this->assertSame($this->context, $this->context->setRequestHeader('X-Foo', 'current-timestamp'));
        $this->assertSame($this->context, $this->context->setRequestHeader('X-Bar', 'current'));

        $request = $this->makeRequest();

        $this->assertTrue(
            (boolean) preg_match(
                '/^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z$/',
                $request->getHeaderLine('X-Foo')
            ),
            'setRequestHeader does not support the magic "current-timestamp" value.'
        );
        $this->assertSame('current', $request->getHeaderLine('X-Bar'));
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getValidDates() {
        return [
            ['date' => 'Wed, 15 Mar 2017 21:28:14 GMT'],
        ];
    }

    /**
     * @dataProvider getValidDates
     * @covers ::isDate
     * @param string $date Date to validate
     */
    public function testIsDateFunctionValidatesDates($date) {
        $this->assertNull($this->context->isDate($date));
    }

    /**
     * @covers ::isDate
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Date is not properly formatted: "invalid date".
     */
    public function testIsDateFunctionCanFail() {
        $this->context->isDate('invalid date');
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getImboConfigFiles() {
        return array_map(function($file) {
            return [basename($file)];
        }, glob(__DIR__ . '/../../behat/imbo-configs/*.php'));
    }

    /**
     * @dataProvider getImboConfigFiles
     * @covers ::setImboConfigHeader
     * @param string $path
     */
    public function testCanSetAConfigHeader($path) {
        $this->assertSame($this->context, $this->context->setImboConfigHeader($path));
        $this->assertSame($path, $this->makeRequest()->getHeaderLine('X-Imbo-Test-Config-File'));
    }

    /**
     * @covers ::setImboConfigHeader
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Configuration file "foobar" does not exist in the "[^"]+" directory\./
     */
    public function testSettingConfigHeaderFailsWithNonExistingFile() {
        $this->context->setImboConfigHeader('foobar');
    }

    /**
     * @covers ::statsAllowedBy
     */
    public function testCanSetStatsAllowedByHeader() {
        $this->assertSame($this->context, $this->context->statsAllowedBy('*'));
        $this->assertSame('*', $this->makeRequest()->getHeaderLine('X-Imbo-Stats-Allowed-By'));
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getAdaptersForFailure() {
        return [
            ['adapter' => 'database', 'header' => 'X-Imbo-Status-Database-Failure'],
            ['adapter' => 'storage', 'header' => 'X-Imbo-Status-Storage-Failure'],
        ];
    }

    /**
     * @dataProvider getAdaptersForFailure
     * @covers ::forceAdapterFailure
     * @param string $adapter
     * @param string $header
     */
    public function testCanForceAdapterFailureBySettingAHeader($adapter, $header) {
        $this->assertSame($this->context, $this->context->forceAdapterFailure($adapter));
        $this->assertSame('1', $this->makeRequest()->getHeaderLine($header));
    }

    /**
     * @covers ::forceAdapterFailure
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid adapter: "foobar".
     */
    public function testThrowsExecptionWhenSpecifyingInvalidAdapterForFailure() {
        $this->context->forceAdapterFailure('foobar');
    }

    /**
     * @covers ::setPublicAndPrivateKey
     * @covers ::signRequest
     */
    public function testCanSignRequest() {
        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey($this->publicKey, $this->privateKey)
                ->signRequest()
        );
        $path = '/path';
        $request = $this->makeRequest($path);

        // Generate the URI and make sure the request URI is the same
        $uri = parse_url($request->getUri());
        $query = [];
        parse_str($uri['query'], $query);

        $data = sprintf('%s|%s|%s|%s',
            $request->getMethod(),
            sprintf('%s%s?publicKey=%s', $this->baseUri, $path, $this->publicKey),
            $this->publicKey,
            $query['timestamp']
        );
        $signature = hash_hmac('sha256', $data, $this->privateKey);

        $this->assertSame($this->publicKey, $query['publicKey']);
        $this->assertSame($signature, $query['signature'], 'Signature mismatch.');
    }

    /**
     * @covers ::setPublicAndPrivateKey
     * @covers ::signRequestUsingHttpHeaders
     * @covers ::signRequest
     */
    public function testCanSignRequestUsingHttpHeaders() {
        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey($this->publicKey, $this->privateKey)
                ->signRequestUsingHttpHeaders()
        );
        $path = '/path';
        $request = $this->makeRequest($path);

        $this->assertTrue($request->hasHeader('X-Imbo-PublicKey'));
        $this->assertTrue($request->hasHeader('X-Imbo-Authenticate-Signature'));
        $this->assertTrue($request->hasHeader('X-Imbo-Authenticate-Timestamp'));

        $data = sprintf('%s|%s|%s|%s',
            $request->getMethod(),
            sprintf('%s%s', $this->baseUri, $path),
            $this->publicKey,
            $request->getHeaderLine('X-Imbo-Authenticate-Timestamp')
        );
        $signature = hash_hmac('sha256', $data, $this->privateKey);

        $this->assertSame($this->publicKey, $request->getHeaderLine('X-Imbo-PublicKey'));
        $this->assertSame($signature, $request->getHeaderLine('X-Imbo-Authenticate-Signature'), 'Signature mismatch.');
    }

    /**
     * @covers ::signRequest
     * @expectedException RuntimeException
     * @expectedExceptionMessage The authentication handler is currently added to the stack. It can not be added more than once.
     */
    public function testCanNotAttachSignatureHandlerMoreThanOnce() {
        $this->context
            ->signRequest()
            ->signRequest();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForAccessTokens() {
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
     * @dataProvider getDataForAccessTokens
     * @covers ::setPublicAndPrivateKey
     * @covers ::appendAccessToken
     * @param string $path
     * @param string $expectedUrl
     */
    public function testCanAppendAccessToken($path, $expectedUrl) {
        $this->assertSame(
            $this->context,
            $this->context
                ->setPublicAndPrivateKey($this->publicKey, $this->privateKey)
                ->appendAccessToken()
        );
        $request = $this->makeRequest($path);

        // Generate the URI and make sure the request URI is the same
        $this->assertSame($expectedUrl, (string) $request->getUri());
    }

    /**
     * @covers ::appendAccessToken
     * @expectedException RuntimeException
     * @expectedExceptionMessage The access token handler is currently added to the stack. It can not be added more than once.
     */
    public function testCanNotAttachAccessTokenHandlerMoreThanOnce() {
        $this->context
            ->appendAccessToken()
            ->appendAccessToken();
    }

    /**
     * @covers ::signRequest
     * @expectedException RuntimeException
     * @expectedExceptionMessage The access token handler is currently added to the stack. These handlers should not be added to the same request.
     */
    public function testCanNotAddBothAccessTokenAndSignatureHandlers() {
        $this->context
            ->appendAccessToken()
            ->signRequest();
    }

    /**
     * @covers ::appendAccessToken
     * @expectedException RuntimeException
     * @expectedExceptionMessage The authentication handler is currently added to the stack. These handlers should not be added to the same request.
     */
    public function testCanNotAddBothSignatureAndAccessTokenHandlers() {
        $this->context
            ->signRequest()
            ->appendAccessToken();
    }

    /**
     * @covers ::addUserImageToImbo
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No keys exist for user "some user".
     */
    public function testThrowsExceptionWhenAddingUserImageWithUnknownUser() {
        $this->context->addUserImageToImbo(__FILE__, 'some user');
    }

    /**
     * @covers ::addUserImageToImbo
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File does not exist: "/some/path".
     */
    public function testThrowsExceptionWhenAddingUserImageWithInvalidFilename() {
        $this->context->addUserImageToImbo('/some/path', 'user');
    }

    /**
     * @covers ::addUserImageToImbo
     * @expectedException RuntimeException
     * @expectedExceptionMessage Image was not successfully added. Response body:
     */
    public function testAddingUserImageToImboFailsWhenImboDoesNotIncludeImageIdentifierInResponse() {
        $this->mockHandler->append(new Response(400, ['Content-Type' => 'application/json'], '{"error": {"message": "some id"}}'));
        $this->context->addUserImageToImbo(FIXTURES_DIR . '/image1.png', 'user');
    }

    /**
     * @covers ::addUserImageToImbo
     */
    public function testCanAddUserImageToImbo() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], '{"imageIdentifier": "some id"}'));

        $this->assertSame(
            $this->context,
            $this->context->addUserImageToImbo(FIXTURES_DIR . '/image1.png', 'user')
        );

        $this->assertSame(
            1,
            $num = count($this->history),
            sprintf('There should be exactly 1 transction in the history, found %d.', $num)
        );

        $request = $this->history[0]['request'];

        $this->assertStringStartsWith(
            'http://localhost:8080/users/user/images?publicKey=publicKey&signature=',
            (string) $request->getUri()
        );
        $this->assertSame('POST', $request->getMethod());
    }

    /**
     * @covers ::addUserImageToImbo
     */
    public function testCanAddUserImageWithMetadataToImbo() {
        $this->mockHandler->append(
            new Response(200, [], '{"imageIdentifier": "imageId"}'),
            new Response(200)
        );

        $this->assertSame(
            $this->context,
            $this->context->addUserImageToImbo(
                FIXTURES_DIR . '/image1.png',
                'user',
                new PyStringNode(['{"foo": "bar"}'], 1)
            )
        );

        $this->assertSame(
            2,
            $num = count($this->history),
            sprintf('There should be exactly 2 transctions in the history, found %d.', $num)
        );

        $imageRequest = $this->history[0]['request'];
        $metadataRequest = $this->history[1]['request'];

        $this->assertStringStartsWith(
            'http://localhost:8080/users/user/images?publicKey=publicKey&signature=',
            (string) $imageRequest->getUri()
        );
        $this->assertSame('POST', $imageRequest->getMethod());

        $this->assertStringStartsWith(
            'http://localhost:8080/users/user/images/imageId/metadata?publicKey=publicKey&signature=',
            (string) $metadataRequest->getUri()
        );
        $this->assertSame('POST', $metadataRequest->getMethod());
    }

    /**
     * @covers ::setClientIp
     */
    public function testCanSetClientIpHeader() {
        $ip = '1.2.3.4';
        $this->assertSame(
            $this->context,
            $this->context
                ->setClientIp($ip)
        );
        $request = $this->makeRequest('/path');

        $this->assertTrue($request->hasHeader('X-Client-Ip'));
        $this->assertSame($ip, $request->getHeaderLine('X-Client-Ip'));
    }

    /**
     * @covers ::applyTransformation
     * @covers ::setRequestQueryParameter
     */
    public function testCanApplyImageTransformation() {
        $this->assertSame(
            $this->context,
            $this->context->applyTransformation('t1')
        );

        $request = $this->makeRequest('/path');

        $this->assertSame(
            'http://localhost:8080/path?t%5B0%5D=t1',
            (string) $request->getUri()
        );
    }

    /**
     * @covers ::applyTransformations
     * @covers ::applyTransformation
     * @covers ::setRequestQueryParameter
     */
    public function testCanApplyImageTransformations() {
        $this->assertSame(
            $this->context,
            $this->context->applyTransformations(new PyStringNode(['t1', 't2', 't3'], 1))
        );

        $request = $this->makeRequest('/path');

        $this->assertSame(
            'http://localhost:8080/path?t%5B0%5D=t1&t%5B1%5D=t2&t%5B2%5D=t3',
            (string) $request->getUri()
        );
    }

    /**
     * @covers ::primeDatabase
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Fixture file "foobar.php" does not exist in "[^"]+"\./
     */
    public function testThrowsExceptionWhenPrimingDatabaseWithScriptThatDoesNotExist() {
        $this->context->primeDatabase('foobar.php');
    }

    /**
     * @covers ::authenticateRequest
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid authentication method: "auth".
     */
    public function testThrowsExceptionWhenSpecifyingInvalidAuthenticationType() {
        $this->context->authenticateRequest('auth');
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getAuthDetails() {
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
                ]
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
                ]
            ],
        ];
    }

    /**
     * @dataProvider getAuthDetails
     * @covers ::setPublicAndPrivateKey
     * @covers ::authenticateRequest
     * @param string $publicKey
     * @param string $privateKey
     * @param string $authMethod
     * @param string $uriRegExp
     * @param array $headers
     */
    public function testCanUseDifferentAuthenticationMethods($publicKey, $privateKey, $authMethod, $uriRegExp, array $headers = []) {
        $this->assertSame(
            $this->context,
            $this->context->setPublicAndPrivateKey($publicKey, $privateKey)
        );
        $this->assertSame(
            $this->context,
            $this->context->authenticateRequest($authMethod)
        );

        $request = $this->makeRequest('/path');
        $this->assertRegExp($uriRegExp, (string) $request->getUri());

        foreach ($headers as $name => $regExp) {
            $this->assertTrue($request->hasHeader($name));
            $this->assertRegExp($regExp, $request->getHeaderLine($name));
        }
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getRequestQueryParams() {
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
     * @dataProvider getRequestQueryParams
     * @covers ::setRequestQueryParameter
     * @param array $params
     * @param string $uri
     */
    public function testCanSetRequestQueryParameters(array $params, $uri) {
        foreach ($params as $param) {
            $this->assertSame(
                $this->context,
                $this->context->setRequestQueryParameter($param['name'], $param['value'])
            );
        }

        $this->assertSame($uri, (string) $this->makeRequest('/path')->getUri());
    }

    /**
     * @covers ::setRequestQueryParameter
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The "t" query parameter already exists and it's not an array, so can't append more values to it.
     */
    public function testThrowsExceptionWhenAppendingArrayParamToRegularParam() {
        $this->context
            ->setRequestQueryParameter('t', 'border')
            ->setRequestQueryParameter('t[]', 'thumb');
    }

    /**
     * @covers ::setRequestParameterToImageIdentifier
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No image identifier exists for image: "/path".
     */
    public function testThrowsExceptionWhenSettingARequestParameterToAnNonExistingImageIdentifier() {
        $this->context->setRequestParameterToImageIdentifier('foo', '/path');
    }

    /**
     * @covers ::setRequestParameterToImageIdentifier
     */
    public function testCanSetQueryParameterToImageIdentifier() {
        $this->mockHandler->append(
            new Response(200, [], '{"imageIdentifier": "1"}'),
            new Response(200, [], '{"imageIdentifier": "2"}'),
            new Response(200, [], '{"imageIdentifier": "3"}')
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->addUserImageToImbo(FIXTURES_DIR . '/image1.png', 'user')
                ->addUserImageToImbo(FIXTURES_DIR . '/image2.png', 'user')
                ->addUserImageToImbo(FIXTURES_DIR . '/image3.png', 'user')
        );

        $this->assertSame(
            $this->context,
            $this->context
                ->setRequestParameterToImageIdentifier('id1', FIXTURES_DIR . '/image1.png')
                ->setRequestParameterToImageIdentifier('id2', FIXTURES_DIR . '/image2.png')
                ->setRequestParameterToImageIdentifier('id3', FIXTURES_DIR . '/image3.png')
        );

        $this->assertSame(
            'http://localhost:8080/path?id1=1&id2=2&id3=3',
            (string) $this->makeRequest('/path')->getUri()
        );
    }

    /**
     * @covers ::generateShortImageUrl
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No image identifier exists for path: "/path".
     */
    public function testThrowsExceptionWheyGeneratingShortImageUrlForNonExistingImage() {
        $this->context->generateShortImageUrl('/path', new PyStringNode([], 1));
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getShortUrlParams() {
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
     * @dataProvider getShortUrlParams
     * @covers ::generateShortImageUrl
     * @param string $image
     * @param string $user
     * @param string $imageIdentifier
     * @param array $params
     */
    public function testCanGenerateShortUrls($image, $user, $imageIdentifier, array $params) {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['imageIdentifier' => $imageIdentifier])),
            new Response(200)
        );

        $this->assertSame(
            $this->context,
            $this->context->addUserImageToImbo($image, $user)
        );

        $this->assertSame(
            $this->context,
            $this->context->generateShortImageUrl(
                $image,
                new PyStringNode([json_encode($params)], 1)
            )
        );

        $this->assertCount(
            2,
            $this->history, 'There should exist exactly 2 requests in the history, found %d.',
            count($this->history)
        );

        $request = $this->history[1]['request'];

        $this->assertSame(
            sprintf('http://localhost:8080/users/user/images/%s/shorturls', $imageIdentifier),
            (string) $request->getUri()
        );

        $this->assertSame('POST', $request->getMethod());

        $this->assertSame(
            array_merge($params, ['imageIdentifier' => $imageIdentifier]),
            json_decode((string) $request->getBody(), true))
        ;
    }

    /**
     * @covers ::specifyAsTheWatermarkImage
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No image exists for path: "/path".
     */
    public function testThrowsExceptionWhenSpecifyingWatermarkImageThatDoesNotExist() {
        $this->context->specifyAsTheWatermarkImage('/path');
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForWatermarkImages() {
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
            ]
        ];
    }

    /**
     * @dataProvider getDataForWatermarkImages
     * @covers ::specifyAsTheWatermarkImage
     * @param string $image
     * @param string $imageIdentifier
     * @param string $params
     * @param string $uri
     */
    public function testCanSpecifyWatermarkImage($image, $imageIdentifier, $params = null, $uri) {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['imageIdentifier' => $imageIdentifier])),
            new Response(200)
        );

        $this->assertSame(
            $this->context,
            $this->context->addUserImageToImbo($image, 'user')
        );

        $this->assertSame(
            $this->context,
            $this->context->specifyAsTheWatermarkImage($image, $params)
        );

        $request = $this->makeRequest('/path');

        $this->assertSame($uri, (string) $request->getUri());
    }
}
