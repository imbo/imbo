<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(Authenticate::class)]
class AuthenticateTest extends ListenerTests
{
    private Authenticate $listener;
    private EventInterface $event;
    private AdapterInterface&MockObject $accessControl;
    private Request&MockObject $request;
    private Response $response;
    private HeaderBag&MockObject $headers;

    public function setUp(): void
    {
        $this->headers = $this->createMock(HeaderBag::class);
        $this->accessControl = $this->createMock(AdapterInterface::class);

        $this->request = $this->createMock(Request::class);
        $this->request->query = new InputBag();
        $this->request->headers = $this->headers;

        $this->response = $this->createStub(Response::class);
        $this->response->headers = $this->createStub(ResponseHeaderBag::class);

        $this->event = $this->getEventStub();

        $this->listener = new Authenticate();
    }

    protected function getListener(): Authenticate
    {
        return $this->listener;
    }

    /**
     * @param ?array{authentication:array{protocol:string}} $config
     */
    protected function getEventStub(?array $config = null): EventInterface
    {
        return $this->createConfiguredStub(EventInterface::class, [
            'getResponse' => $this->response,
            'getRequest' => $this->request,
            'getAccessControl' => $this->accessControl,
            'getConfig' => $config ?: [
                'authentication' => [
                    'protocol' => 'incoming',
                ],
            ],
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenAuthInfoIsMissing(): void
    {
        $this->headers
            ->expects($this->once())
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(false);

        $this->headers
            ->expects($this->once())
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(null);

        $this->expectExceptionObject(new RuntimeException('Missing authentication timestamp', Response::HTTP_BAD_REQUEST));
        $this->listener->authenticate($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenSignatureIsMissing(): void
    {
        $this->headers
            ->method('has')
            ->willReturnCallback(
                static function (string $header): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header]) {
                        [0, 'x-imbo-authenticate-timestamp'],
                        [1, 'x-imbo-authenticate-signature'] => true,
                    };
                },
            );

        $this->headers
            ->method('get')
            ->willReturnCallback(
                static function (string $header, ?string $value) {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header, $value]) {
                        [0, 'x-imbo-authenticate-timestamp', null] => gmdate('Y-m-d\TH:i:s\Z'),
                        [1, 'x-imbo-authenticate-signature', null] => null,
                    };
                },
            );

        $this->expectExceptionObject(new RuntimeException('Missing authentication signature', Response::HTTP_BAD_REQUEST));
        $this->listener->authenticate($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenTimestampIsInvalid(): void
    {
        $this->headers
            ->method('has')
            ->willReturnCallback(
                static function (string $header): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header]) {
                        [0, 'x-imbo-authenticate-timestamp'],
                        [1, 'x-imbo-authenticate-signature'] => true,
                    };
                },
            );

        $this->headers
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn('some string');

        $this->expectExceptionObject(new RuntimeException('Invalid timestamp: some string', Response::HTTP_BAD_REQUEST));
        $this->listener->authenticate($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenTimestampHasExpired(): void
    {
        $this->headers
            ->method('has')
            ->willReturnCallback(
                static function (string $header): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header]) {
                        [0, 'x-imbo-authenticate-timestamp'],
                        [1, 'x-imbo-authenticate-signature'] => true,
                    };
                },
            );

        $this->headers
            ->method('get')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn('2010-07-10T20:02:10Z');

        $this->expectExceptionObject(new RuntimeException('Timestamp has expired: 2010-07-10T20:02:10Z', Response::HTTP_BAD_REQUEST));
        $this->listener->authenticate($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenSignatureDoesNotMatch(): void
    {
        $this->headers
            ->method('has')
            ->willReturnCallback(
                static function (string $header): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header]) {
                        [0, 'x-imbo-authenticate-timestamp'],
                        [1, 'x-imbo-authenticate-signature'] => true,
                    };
                },
            );

        $this->headers
            ->method('get')
            ->willReturnCallback(
                static function (string $header) {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header]) {
                        [0, 'x-imbo-authenticate-timestamp'] => gmdate('Y-m-d\TH:i:s\Z'),
                        [1, 'x-imbo-authenticate-signature'] => 'foobar',
                    };
                },
            );

        $this->request
            ->expects($this->once())
            ->method('getPublicKey')
            ->willReturn('publickey');

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->with('publickey')
            ->willReturn('privateKey');

        $this->expectExceptionObject(new RuntimeException('Signature mismatch', Response::HTTP_BAD_REQUEST));
        $this->listener->authenticate($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testApprovesValidSignature(): void
    {
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
            ->method('has')
            ->willReturnCallback(
                static function (string $header): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header]) {
                        [0, 'x-imbo-authenticate-timestamp'],
                        [1, 'x-imbo-authenticate-signature'] => true,
                    };
                },
            );

        $this->headers
            ->method('get')
            ->willReturnCallback(
                static function (string $header) use ($timestamp, $signature): string {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header]) {
                        [0, 'x-imbo-authenticate-timestamp'] => $timestamp,
                        [1, 'x-imbo-authenticate-signature'] => $signature,
                    };
                },
            );

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

    #[AllowMockObjectsWithoutExpectations]
    public function testApprovesValidSignatureWithAuthInfoFromQueryParameters(): void
    {
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
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(false);

        $this->headers
            ->method('get')
            ->willReturnCallback(
                static function (string $header, string $value) use ($timestamp, $signature): string {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header, $value]) {
                        [0, 'x-imbo-authenticate-timestamp', $timestamp] => $timestamp,
                        [1, 'x-imbo-authenticate-signature', $signature] => $signature,
                    };
                },
            );

        $this->request->query = new InputBag([
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);
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

    #[DataProvider('getRewrittenSignatureData')]
    #[AllowMockObjectsWithoutExpectations]
    public function testApprovesSignaturesWhenConfigurationForcesProtocol(string $serverUrl, string $protocol, string $authHeader, bool $shouldMatch, string $signature, string $timestamp): void
    {
        if (!$shouldMatch) {
            $this->expectExceptionObject(new RuntimeException('Signature mismatch', Response::HTTP_BAD_REQUEST));
        }

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->willReturn('key');

        $this->headers
            ->method('has')
            ->with('x-imbo-authenticate-timestamp')
            ->willReturn(false);

        $this->headers
            ->method('get')
            ->willReturnCallback(
                static function (string $header, string $value) use ($timestamp, $signature): string {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $header, $value]) {
                        [0, 'x-imbo-authenticate-timestamp', $timestamp] => $timestamp,
                        [1, 'x-imbo-authenticate-signature', $signature] => $signature,
                    };
                },
            );

        $this->request->query = new InputBag([
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);
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

        $this->listener->authenticate($this->getEventStub([
            'authentication' => [
                'protocol' => $protocol,
            ],
        ]));
    }

    /**
     * @return array<array{serverUrl:string,protocol:string,authHeader:string,shouldMatch:bool,signature:string,timestamp:string}>
     */
    public static function getRewrittenSignatureData(): array
    {
        return array_map(
            /**
             * @param array{clientSideUrl:string,serverUrl:string,protocol:string,authHeader:string,shouldMatch:bool} $dataSet
             * @return array{serverUrl:string,protocol:string,authHeader:string,shouldMatch:bool,signature:string,timestamp:string}
             */
            function (array $dataSet): array {
                $httpMethod = 'PUT';
                $publicKey = 'christer';
                $privateKey = 'key';
                $timestamp = gmdate('Y-m-d\TH:i:s\Z');
                $data = sprintf('%s|%s|%s|%s', $httpMethod, $dataSet['clientSideUrl'], $publicKey, $timestamp);
                $signature = hash_hmac('sha256', $data, $privateKey);

                return [
                    'serverUrl' => $dataSet['serverUrl'] . '?signature=' . $signature . '&timestamp=' . $timestamp,
                    'protocol' => $dataSet['protocol'],
                    'authHeader' => $dataSet['authHeader'],
                    'shouldMatch' => $dataSet['shouldMatch'],
                    'signature' => $signature,
                    'timestamp' => $timestamp,
                ];
            },
            [
                [
                    'clientSideUrl' => 'http://imbo/users/christer/images/image',
                    'serverUrl' => 'http://imbo/users/christer/images/image',
                    'protocol' => 'http',
                    'authHeader' => 'http://imbo/users/christer/images/image',
                    'shouldMatch' => true,
                ],
                [
                    'clientSideUrl' => 'http://imbo/users/christer/images/image',
                    'serverUrl' => 'https://imbo/users/christer/images/image',
                    'protocol' => 'http',
                    'authHeader' => 'http://imbo/users/christer/images/image',
                    'shouldMatch' => true,
                ],
                [
                    'clientSideUrl' => 'https://imbo/users/christer/images/image',
                    'serverUrl' => 'http://imbo/users/christer/images/image',
                    'protocol' => 'https',
                    'authHeader' => 'https://imbo/users/christer/images/image',
                    'shouldMatch' => true,
                ],
                // URL gets rewritten to HTTPS, which doesn't match what was used for signing
                [
                    'clientSideUrl' => 'http://imbo/users/christer/images/image',
                    'serverUrl' => 'http://imbo/users/christer/images/image',
                    'protocol' => 'https',
                    'authHeader' => 'https://imbo/users/christer/images/image',
                    'shouldMatch' => false,
                ],
                // If we allow both protocols, it shouldn't matter if its signed with HTTP or HTTPS
                [
                    'clientSideUrl' => 'http://imbo/users/christer/images/image',
                    'serverUrl' => 'https://imbo/users/christer/images/image',
                    'protocol' => 'both',
                    'authHeader' => 'http://imbo/users/christer/images/image, https://imbo/users/christer/images/image',
                    'shouldMatch' => true,
                ],
                [
                    'clientSideUrl' => 'https://imbo/users/christer/images/image',
                    'serverUrl' => 'http://imbo/users/christer/images/image',
                    'protocol' => 'both',
                    'authHeader' => 'http://imbo/users/christer/images/image, https://imbo/users/christer/images/image',
                    'shouldMatch' => true,
                ],
                // Different URLs should always fail, obviously
                [
                    'clientSideUrl' => 'https://imbo/users/christer/images/someotherimage',
                    'serverUrl' => 'http://imbo/users/christer/images/image',
                    'protocol' => 'both',
                    'authHeader' => 'http://imbo/users/christer/images/image, https://imbo/users/christer/images/image',
                    'shouldMatch' => false,
                ],
                // Different URLs should always fail, even when forced to http/https
                [
                    'clientSideUrl' => 'https://imbo/users/christer/images/someotherimage',
                    'serverUrl' => 'http://imbo/users/christer/images/image',
                    'protocol' => 'http',
                    'authHeader' => 'http://imbo/users/christer/images/image',
                    'shouldMatch' => false,
                ],
                [
                    'clientSideUrl' => 'http://imbo/users/christer/images/someotherimage',
                    'serverUrl' => 'http://imbo/users/christer/images/image',
                    'protocol' => 'https',
                    'authHeader' => 'https://imbo/users/christer/images/image',
                    'shouldMatch' => false,
                ],
            ],
        );
    }
}
