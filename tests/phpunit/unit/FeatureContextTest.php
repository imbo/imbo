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
        $this->client = new Client([
            'handler' => $this->handlerStack,
            'base_uri' => $this->baseUri,
        ]);
        $this->cacheUtil = $this->createMock('Micheh\Cache\CacheUtil');

        $this->context = new FeatureContext($this->cacheUtil);
        $this->context->setClient($this->client);
    }

    /**
     * Convenience method to make a single request
     *
     * @param string $path
     * @return Request
     */
    private function makeRequest($path = '/somepath') {
        $this->handlerStack->push(Middleware::history($this->history));
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath($path);

        return $this->history[0]['request'];
    }

    /**
     * @covers ::setClient
     */
    public function testCanSetAnApiClient() {
        $handlerStack = $this->createMock('GuzzleHttp\HandlerStack');
        $handlerStack
            ->expects($this->once())
            ->method('remove')
            ->with($this->isType('string'));
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
}
