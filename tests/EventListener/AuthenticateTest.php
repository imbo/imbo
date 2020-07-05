<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\EventManager\Event;
use Imbo\EventListener\Authenticate;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @coversDefaultClass Imbo\EventListener\Authenticate
 */
class AuthenticateTest extends ListenerTests {
    private $listener;
    private $event;
    private $accessControl;
    private $request;
    private $response;
    private $query;
    private $headers;

    public function setUp() : void {
        $this->query = $this->createMock(ParameterBag::class);
        $this->headers = $this->createMock(HeaderBag::class);
        $this->accessControl = $this->createMock(AdapterInterface::class);

        $this->request = $this->createMock(Request::class);
        $this->request->query = $this->query;
        $this->request->headers = $this->headers;

        $this->response = $this->createMock(Response::class);
        $this->response->headers = $this->createMock(HeaderBag::class);

        $this->event = $this->getEventMock();

        $this->listener = new Authenticate();
    }

    protected function getListener() : Authenticate {
        return $this->listener;
    }

    protected function getEventMock($config = null) : Event {
        return $this->createConfiguredMock(Event::class, [
            'getResponse' => $this->response,
            'getRequest' => $this->request,
            'getAccessControl' => $this->accessControl,
            'getConfig' => $config ?: [
                'authentication' => [
                    'protocol' => 'incoming'
                ]
            ]
        ]);
    }

    /**
     * @covers ::authenticate
     */
    public function testThrowsExceptionWhenAuthInfoIsMissing() : void {
        $this->headers
            ->expects($this->at(0))
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(false);

        $this->headers
            ->expects($this->at(1))
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(null);

        $this->expectExceptionObject(new RuntimeException('Missing authentication timestamp', 400));
        $this->listener->authenticate($this->event);
    }

    /**
     * @covers ::authenticate
     */
    public function testThrowsExceptionWhenSignatureIsMissing() : void {
        $this->headers
            ->expects($this->at(0))
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(1))
            ->method('has')
            ->with('x-imbo-authenticate-signature')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(2))
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(gmdate('Y-m-d\TH:i:s\Z'));

        $this->expectExceptionObject(new RuntimeException('Missing authentication signature', 400));
        $this->listener->authenticate($this->event);
    }

    /**
     * @covers ::authenticate
     * @covers ::timestampIsValid
     */
    public function testThrowsExceptionWhenTimestampIsInvalid() : void {
        $this->headers
            ->expects($this->at(0))
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(1))
            ->method('has')
            ->with('x-imbo-authenticate-signature')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(2))
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn('some string');

        $this->expectExceptionObject(new RuntimeException('Invalid timestamp: some string', 400));
        $this->listener->authenticate($this->event);
    }

    /**
     * @covers ::authenticate
     * @covers ::timestampHasExpired
     */
    public function testThrowsExceptionWhenTimestampHasExpired() : void {
        $this->headers
            ->expects($this->at(0))
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(1))
            ->method('has')
            ->with('x-imbo-authenticate-signature')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(2))
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn('2010-07-10T20:02:10Z');

        $this->expectExceptionObject(new RuntimeException('Timestamp has expired: 2010-07-10T20:02:10Z', 400));
        $this->listener->authenticate($this->event);
    }

    /**
     * @covers ::authenticate
     */
    public function testThrowsExceptionWhenSignatureDoesNotMatch() : void {
        $this->headers
            ->expects($this->at(0))
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(1))
            ->method('has')
            ->with('x-imbo-authenticate-signature')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(2))
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(gmdate('Y-m-d\TH:i:s\Z'));

        $this->headers
            ->expects($this->at(3))
            ->method('get')
            ->with('x-imbo-authenticate-signature')
            ->willReturn('foobar');

        $this->request
            ->expects($this->once())
            ->method('getPublicKey')
            ->willReturn('publickey');

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->with('publickey')
            ->willReturn('privateKey');

        $this->expectExceptionObject(new RuntimeException('Signature mismatch', 400));
        $this->listener->authenticate($this->event);
    }

    /**
     * @covers ::authenticate
     * @covers ::signatureIsValid
     * @covers ::timestampIsValid
     * @covers ::timestampHasExpired
     */
    public function testApprovesValidSignature() : void {
        $httpMethod = 'GET';
        $url = 'http://imbo/users/christer/images/image';
        $publicKey = 'christer';
        $privateKey = 'key';
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $data = sprintf('%s|%s|%s|%s', $httpMethod, $url, $publicKey, $timestamp);
        $signature = hash_hmac('sha256', $data, $privateKey);

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->willReturn($privateKey);

        $this->headers
            ->expects($this->at(0))
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(1))
            ->method('has')
            ->with('x-imbo-authenticate-signature')
            ->willReturn(true);

        $this->headers
            ->expects($this->at(2))
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn($timestamp);

        $this->headers
            ->expects($this->at(3))
            ->method('get')
            ->with('x-imbo-authenticate-signature')
            ->willReturn($signature);

        $this->request
            ->expects($this->once())
            ->method('getRawUri')
            ->willReturn($url);

        $this->request
            ->expects($this->once())
            ->method('getPublicKey')
            ->willReturn($publicKey);

        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn($httpMethod);

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-AuthUrl', $url);

        $this->response->headers = $responseHeaders;

        $this->listener->authenticate($this->event);
    }

    /**
     * @covers ::authenticate
     * @covers ::signatureIsValid
     * @covers ::timestampIsValid
     * @covers ::timestampHasExpired
     */
    public function testApprovesValidSignatureWithAuthInfoFromQueryParameters() : void {
        $httpMethod = 'GET';
        $url = 'http://imbo/users/christer/images/image';
        $publicKey = 'christer';
        $privateKey = 'key';
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $data = sprintf('%s|%s|%s|%s', $httpMethod, $url, $publicKey, $timestamp);
        $signature = hash_hmac('sha256', $data, $privateKey);
        $rawUrl = sprintf('%s?signature=%s&timestamp=%s', $url, $signature, $timestamp);

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->willReturn($privateKey);

        $this->headers
            ->expects($this->at(0))
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(false);

        $this->headers
            ->expects($this->at(1))
            ->method('get')
            ->with('x-imbo-authenticate-timestamp', $timestamp)
            ->willReturn($timestamp);

        $this->headers
            ->expects($this->at(2))
            ->method('get')
            ->with('x-imbo-authenticate-signature', $signature)
            ->willReturn($signature);

        $this->query
            ->expects($this->at(0))
            ->method('get')
            ->with('timestamp')
            ->willReturn($timestamp);

        $this->query
            ->expects($this->at(1))
            ->method('get')
            ->with('signature')
            ->willReturn($signature);

        $this->request
            ->expects($this->once())
            ->method('getRawUri')
            ->willReturn($rawUrl);

        $this->request
            ->expects($this->once())
            ->method('getPublicKey')
            ->willReturn($publicKey);

        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn($httpMethod);

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-AuthUrl', $url);

        $this->response->headers = $responseHeaders;

        $this->listener->authenticate($this->event);
    }

    public function getRewrittenSignatureData() : array {
        return array_map(function($dataSet) {
            $httpMethod = 'PUT';
            $publicKey = 'christer';
            $privateKey = 'key';
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $data = sprintf('%s|%s|%s|%s', $httpMethod, $dataSet[0], $publicKey, $timestamp);
            $signature = hash_hmac('sha256', $data, $privateKey);
            return [
                // Server-reported URL
                $dataSet[1] . '?signature=' . $signature . '&timestamp=' . $timestamp,
                // Imbo configured protocol
                $dataSet[2],
                // Expected auth URL header
                $dataSet[3],
                // Should match?
                $dataSet[4],
                // Signature
                $signature,
                // Timestamp
                $timestamp,
            ];
        }, [
            [
                // URL used for signing on client side
                'http://imbo/users/christer/images/image',
                // URL reported by server (in case of misconfiguration/proxies etc)
                'http://imbo/users/christer/images/image',
                // Protocol configuration on Imbo
                'http',
                // Expected auth URL header (all attempted variants)
                'http://imbo/users/christer/images/image',
                // Should it match?
                true
            ],
            [
                'http://imbo/users/christer/images/image',
                'https://imbo/users/christer/images/image',
                'http',
                'http://imbo/users/christer/images/image',
                true
            ],
            [
                'https://imbo/users/christer/images/image',
                'http://imbo/users/christer/images/image',
                'https',
                'https://imbo/users/christer/images/image',
                true
            ],
            // URL gets rewritten to HTTPS, which doesn't match what was used for signing
            [
                'http://imbo/users/christer/images/image',
                'http://imbo/users/christer/images/image',
                'https',
                'https://imbo/users/christer/images/image',
                false
            ],
            // If we allow both protocols, it shouldn't matter if its signed with HTTP or HTTPS
            [
                'http://imbo/users/christer/images/image',
                'https://imbo/users/christer/images/image',
                'both',
                'http://imbo/users/christer/images/image, https://imbo/users/christer/images/image',
                true
            ],
            [
                'https://imbo/users/christer/images/image',
                'http://imbo/users/christer/images/image',
                'both',
                'http://imbo/users/christer/images/image, https://imbo/users/christer/images/image',
                true
            ],
            // Different URLs should always fail, obviously
            [
                'https://imbo/users/christer/images/someotherimage',
                'http://imbo/users/christer/images/image',
                'both',
                'http://imbo/users/christer/images/image, https://imbo/users/christer/images/image',
                false
            ],
            // Different URLs should always fail, even when forced to http/https
            [
                'https://imbo/users/christer/images/someotherimage',
                'http://imbo/users/christer/images/image',
                'http',
                'http://imbo/users/christer/images/image',
                false
            ],
            [
                'http://imbo/users/christer/images/someotherimage',
                'http://imbo/users/christer/images/image',
                'https',
                'https://imbo/users/christer/images/image',
                false
            ],
        ]);
    }

    /**
     * @dataProvider getRewrittenSignatureData
     * @covers ::authenticate
     * @covers ::signatureIsValid
     * @covers ::timestampIsValid
     * @covers ::timestampHasExpired
     */
    public function testApprovesSignaturesWhenConfigurationForcesProtocol(string $serverUrl, string $protocol, string $authHeader, bool $shouldMatch, string $signature, string $timestamp) : void {
        if (!$shouldMatch) {
            $this->expectExceptionObject(new RuntimeException('Signature mismatch', 400));
        }

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->willReturn('key');

        $this->headers
            ->expects($this->at(0))
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(false);

        $this->headers
            ->expects($this->at(1))
            ->method('get')
            ->with('x-imbo-authenticate-timestamp', $timestamp)
            ->willReturn($timestamp);

        $this->headers
            ->expects($this->at(2))
            ->method('get')
            ->with('x-imbo-authenticate-signature', $signature)
            ->willReturn($signature);

        $this->query
            ->expects($this->at(0))
            ->method('get')
            ->with('timestamp')
            ->willReturn($timestamp);

        $this->query
            ->expects($this->at(1))
            ->method('get')
            ->with('signature')
            ->willReturn($signature);

        $this->request
            ->expects($this->once())
            ->method('getRawUri')
            ->willReturn($serverUrl);

        $this->request
            ->expects($this->once())
            ->method('getPublicKey')
            ->willReturn('christer');

        $this->request
            ->expects($this->any())
            ->method('getMethod')
            ->willReturn('PUT');

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-AuthUrl', $authHeader);

        $this->response->headers = $responseHeaders;

        $this->listener->authenticate($this->getEventMock([
            'authentication' => [
                'protocol' => $protocol,
            ]
        ]));
    }
}
