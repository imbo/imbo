<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface as AccessControlAdapter;
use Imbo\EventListener\AccessToken\AccessTokenInterface;
use Imbo\EventManager\Event;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\ConfigurationException;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(AccessToken::class)]
class AccessTokenTest extends ListenerTests
{
    private AccessToken $listener;
    private EventInterface&MockObject $event;
    private AccessControlAdapter&MockObject $accessControl;
    private Request&MockObject $request;
    private Response $response;
    private ResponseHeaderBag&MockObject $responseHeaders;

    public function setUp(): void
    {
        $this->accessControl = $this->createMock(AccessControlAdapter::class);

        $this->request = $this->createMock(Request::class);
        $this->request->query = new InputBag();

        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->response = $this->createStub(Response::class);
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->getEventMock();

        $this->listener = new AccessToken();
    }

    protected function getListener(): AccessToken
    {
        return $this->listener;
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRequestWithBogusAccessToken(): void
    {
        $this->request->query = new InputBag(['accessToken' => '/string']);
        $this->request
            ->method('getRawUri')
            ->willReturn('someuri');

        $this->request
            ->method('getPublicKey')
            ->willReturn('some-key');

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->willReturn('private-key');

        $this->expectExceptionObject(new RuntimeException('Incorrect access token', Response::HTTP_BAD_REQUEST));
        $this->listener->checkAccessToken($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionIfAnAccessTokenIsMissingFromTheRequestWhenNotWhitelisted(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getName')
            ->willReturn('image.get');

        $this->expectExceptionObject(new RuntimeException('Missing access token', Response::HTTP_BAD_REQUEST));
        $this->listener->checkAccessToken($this->event);
    }

    #[DataProvider('getFilterData')]
    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsFilters(array $filter, array $transformations, bool $whitelisted): void
    {
        $listener = new AccessToken($filter);

        if (!$whitelisted) {
            $this->expectExceptionObject(new RuntimeException('Missing access token', Response::HTTP_BAD_REQUEST));
        }

        $this->event
            ->expects($this->once())
            ->method('getName')
            ->willReturn('image.get');

        $this->request
            ->method('getTransformations')
            ->willReturn($transformations);

        $listener->checkAccessToken($this->event);
    }

    #[DataProvider('getAccessTokens')]
    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionOnIncorrectToken(string $url, string $token, string $privateKey, bool $correct): void
    {
        if (!$correct) {
            $this->expectExceptionObject(new RuntimeException('Incorrect access token', Response::HTTP_BAD_REQUEST));
        }

        $this->request->query = new InputBag(['accessToken' => $token]);
        $this->request
            ->expects($this->atLeastOnce())
            ->method('getRawUri')
            ->willReturn(urldecode($url));

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getUriAsIs')
            ->willReturn($url);

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getPublicKey')
            ->willReturn('some-key');

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->willReturn($privateKey);

        $this->listener->checkAccessToken($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWillSkipValidationWhenShortUrlHeaderIsPresent(): void
    {
        $this->responseHeaders
            ->expects($this->once())
            ->method('has')
            ->with('X-Imbo-ShortUrl')
            ->willReturn(true);

        $this->listener->checkAccessToken($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWillAcceptBothProtocolsIfConfiguredTo(): void
    {
        $privateKey = 'foobar';
        $baseUrl = '//imbo.host/users/some-user/imgUrl.png?t[]=smartSize:width=320,height=240';

        $this->accessControl
            ->method('getPrivateKey')
            ->willReturn($privateKey);

        foreach (['http', 'https'] as $signedProtocol) {
            $token = hash_hmac('sha256', $signedProtocol . ':' . $baseUrl, $privateKey);

            foreach (['http', 'https'] as $protocol) {
                $url = $protocol . ':' . $baseUrl . '&accessToken=' . $token;

                $request = $this->createConfiguredStub(Request::class, [
                    'getRawUri' => urldecode($url),
                    'getUriAsIs' => $url,
                    'getPublicKey' => 'some-key',
                ]);
                $request->query = new InputBag(['accessToken' => $token]);

                $event = $this->createConfiguredStub(Event::class, [
                    'getAccessControl' => $this->accessControl,
                    'getRequest' => $request,
                    'getResponse' => $this->response,
                    'getConfig' => [
                        'authentication' => [
                            'protocol' => 'both',
                        ],
                    ],
                ]);

                $this->assertTrue(
                    $this->listener->checkAccessToken($event),
                    'Expected method to return true to signal successful comparison',
                );
            }
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testAccessTokenArgumentKey(): void
    {
        $url = 'http://imbo/users/christer';
        $token = '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a';
        $privateKey = 'private key';

        $listener = new AccessToken([
            'accessTokenGenerator' => new AccessToken\SHA256(['argumentKeys' => ['foo']]),
        ]);

        $this->request->query = new InputBag(['foo' => $token]);
        $this->request
            ->expects($this->atLeastOnce())
            ->method('getRawUri')
            ->willReturn(urldecode($url));

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getUriAsIs')
            ->willReturn($url);

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getPublicKey')
            ->willReturn('some-key');

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->willReturn($privateKey);

        $listener->checkAccessToken($this->event);
    }

    #[DataProvider('getAccessTokensForMultipleGenerator')]
    #[AllowMockObjectsWithoutExpectations]
    public function testMultipleAccessTokensGenerator(string $url, string $token, string $privateKey, bool $correct, string $argumentKey): void
    {
        if (!$correct) {
            $this->expectExceptionObject(new RuntimeException('Incorrect access token', Response::HTTP_BAD_REQUEST));
        }

        $dummyAccessToken = $this->createConfiguredStub(AccessTokenInterface::class, [
            'generateSignature' => 'dummy',
        ]);

        $listener = new AccessToken([
            'accessTokenGenerator' => new AccessToken\MultipleAccessTokenGenerators([
                'generators' => [
                    'accessToken' => new AccessToken\SHA256(),
                    'dummy' => $dummyAccessToken,
                ],
            ]),
        ]);

        $this->request->query = new InputBag([$argumentKey => $token]);
        $this->request
            ->expects($this->atLeastOnce())
            ->method('getRawUri')
            ->willReturn(urldecode($url));

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getUriAsIs')
            ->willReturn($url);

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getPublicKey')
            ->willReturn('some-key');

        $this->accessControl
            ->expects($this->once())
            ->method('getPrivateKey')
            ->willReturn($privateKey);

        $listener->checkAccessToken($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testConfigurationExceptionOnInvalidAccessTokenGenerator(): void
    {
        $this->expectExceptionObject(new ConfigurationException('Invalid accessTokenGenerator', Response::HTTP_INTERNAL_SERVER_ERROR));

        new AccessToken(['accessTokenGenerator' => new StdClass()]);
    }

    #[DataProvider('getRewrittenAccessTokenData')]
    #[AllowMockObjectsWithoutExpectations]
    public function testWillRewriteIncomingUrlToConfiguredProtocol(string $accessToken, string $url, string $protocol, bool $correct): void
    {
        if (!$correct) {
            $this->expectExceptionObject(new RuntimeException('Incorrect access token', Response::HTTP_BAD_REQUEST));
        }

        $event = $this->getEventMock([
            'authentication' => [
                'protocol' => $protocol,
            ],
        ]);

        $url = $url . '&accessToken=' . $accessToken;

        $this->request->query = new InputBag(['accessToken' => $accessToken]);
        $this->request
            ->method('getRawUri')
            ->willReturn(urldecode($url));

        $this->request
            ->method('getUriAsIs')
            ->willReturn($url);

        $this->request
            ->method('getPublicKey')
            ->willReturn('some-key');

        $this->accessControl
            ->method('getPrivateKey')
            ->willReturn('foobar');

        $this->assertTrue(
            $this->listener->checkAccessToken($event),
            'Expected method to return true to signal successful comparison',
        );
    }

    protected function getEventMock(?array $config = null): EventInterface&MockObject
    {
        return $this->createConfiguredMock(EventInterface::class, [
            'getAccessControl' => $this->accessControl,
            'getRequest'       => $this->request,
            'getResponse'      => $this->response,
            'getConfig'        => $config ?: [
                'authentication' => [
                    'protocol' => 'incoming',
                ],
            ],
        ]);
    }

    /**
     * @return array<array{filter:array{transformations?:array{whitelist?:array<string>,blacklist?:array<string>}},transformations:array<array{name:string,params:array}>,whitelisted:bool}>
     */
    public static function getFilterData(): array
    {
        return [
            'no filter and no transformations' => [
                'filter' => [],
                'transformations' => [],
                'whitelisted' => false,
            ],
            // @see https://github.com/imbo/imbo/issues/258
            'configured filters, but no transformations in the request' => [
                'filter' => ['transformations' => ['whitelist' => ['border']]],
                'transformations' => [],
                'whitelisted' => false,
            ],
            [
                'filter' => [],
                'transformations' => [
                    ['name' => 'border', 'params' => []],
                ],
                'whitelisted' => false,
            ],
            [
                'filter' => ['transformations' => ['whitelist' => ['border']]],
                'transformations' => [
                    ['name' => 'border', 'params' => []],
                ],
                'whitelisted' => true,
            ],
            [
                'filter' => ['transformations' => ['whitelist' => ['border']]],
                'transformations' => [
                    ['name' => 'border', 'params' => []],
                    ['name' => 'thumbnail', 'params' => []],
                ],
                'whitelisted' => false,
            ],
            [
                'filter' => ['transformations' => ['blacklist' => ['border']]],
                'transformations' => [
                    ['name' => 'thumbnail', 'params' => []],
                ],
                'whitelisted' => true,
            ],
            [
                'filter' => ['transformations' => ['blacklist' => ['border']]],
                'transformations' => [
                    ['name' => 'border', 'params' => []],
                    ['name' => 'thumbnail', 'params' => []],
                ],
                'whitelisted' => false,
            ],
            [
                'filter' => ['transformations' => ['whitelist' => ['border'], 'blacklist' => ['thumbnail']]],
                'transformations' => [
                    ['name' => 'border', 'params' => []],
                ],
                'whitelisted' => true,
            ],
            [
                'filter' => ['transformations' => ['whitelist' => ['border'], 'blacklist' => ['thumbnail']]],
                'transformations' => [
                    ['name' => 'canvas', 'params' => []],
                ],
                'whitelisted' => false,
            ],
            [
                'filter' => ['transformations' => ['whitelist' => ['border'], 'blacklist' => ['border']]],
                'transformations' => [
                    ['name' => 'border', 'params' => []],
                ],
                'whitelisted' => false,
            ],
        ];
    }

    /**
     * @return array<array{url:string,token:string,privateKey:string,correct:bool}>
     */
    public static function getAccessTokens(): array
    {
        return [
            [
                'url' => 'http://imbo/users/christer',
                'token' => 'some access token',
                'privateKey' => 'private key',
                'correct' => false,
            ],
            [
                'url' => 'http://imbo/users/christer',
                'token' => '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a',
                'privateKey' => 'private key',
                'correct' => true,
            ],
            [
                'url' => 'http://imbo/users/christer',
                'token' => '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a',
                'privateKey' => 'other private key',
                'correct' => false,
            ],
            // Test that checking URL "as is" works properly. This is for backwards compatibility.
            [
                'url' => 'http://imbo/users/christer?t[]=thumbnail%3Awidth%3D40%2Cheight%3D40%2Cfit%3Doutbound',
                'token' => 'f0166cb4f7c8eabbe82c5d753f681ed53bcfa10391d4966afcfff5806cc2bff4',
                'privateKey' => 'some random private key',
                'correct' => true,
            ],
            // Test checking URL against incorrect protocol
            [
                'url' => 'https://imbo/users/christer',
                'token' => '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a',
                'privateKey' => 'private key',
                'correct' => false,
            ],
            // Test that URLs with t[0] and t[] can use the same accessToken
            [
                'url' => 'http://imbo/users/imbo/images/foobar?t%5B0%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B1%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50',
                'token' => '1ae7643a70e68377502c30ba54d0ffbfedd67a1f3c4b3f038a42c0ed17ad3551',
                'privateKey' => 'foo',
                'correct' => true,
            ],
            [
                'url' => 'http://imbo/users/imbo/images/foobar?t%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50',
                'token' => '1ae7643a70e68377502c30ba54d0ffbfedd67a1f3c4b3f038a42c0ed17ad3551',
                'privateKey' => 'foo',
                'correct' => true,
            ],
            // and that they still break if something else is changed
            [
                'url' => 'http://imbo/users/imbo/images/foobar?g%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50',
                'token' => '1ae7643a70e68377502c30ba54d0ffbfedd67a1f3c4b3f038a42c0ed17ad3551',
                'privateKey' => 'foo',
                'correct' => false,
            ],
        ];
    }

    /**
     * @return array<array{url:string,token:string,privateKey:string,correct:bool,argumentKey:string}>
     */
    public static function getAccessTokensForMultipleGenerator(): array
    {
        $tokens = [];

        foreach (self::getAccessTokens() as $token) {
            $token['argumentKey'] = 'accessToken';
            $tokens[] = $token;
        }

        return array_merge($tokens, [
            [
                'url' => 'http://imbo/users/imbo/images/foobar?t%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50',
                'token' => 'dummy',
                'privateKey' => 'foo',
                'correct' => true,
                'argumentKey' => 'dummy',
            ],
            [
                'url' => 'http://imbo/users/imbo/images/foobar?t%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50123',
                'token' => 'dummy',
                'privateKey' => 'foobar',
                'correct' => true,
                'argumentKey' => 'dummy',
            ],
            [
                'url' => 'http://imbo/users/imbo/images/foobar?t%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50123',
                'token' => 'boop',
                'privateKey' => 'foobar',
                'correct' => false,
                'argumentKey' => 'dummy',
            ],
        ]);
    }

    /**
     * @return array<array{accessToken:string,url:string,protocol:string,correct:bool}>
     */
    public static function getRewrittenAccessTokenData(): array
    {
        $getAccessToken = fn (string $url): string => hash_hmac('sha256', $url, 'foobar');

        return [
            [
                'accessToken' => $getAccessToken('http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'url' => 'http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'protocol' => 'http',
                'correct' => true,
            ],
            [
                'accessToken' => $getAccessToken('http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'url' => 'https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'protocol' => 'http',
                'correct' => true,
            ],
            [
                'accessToken' => $getAccessToken('https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'url' => 'https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'protocol' => 'http',
                'correct' => false,
            ],
            [
                'accessToken' => $getAccessToken('https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'url' => 'http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'protocol' => 'http',
                'correct' => false,
            ],
            [
                'accessToken' => $getAccessToken('https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'url' => 'http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'protocol' => 'https',
                'correct' => true,
            ],
            [
                'accessToken' => $getAccessToken('https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'url' => 'https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'protocol' => 'https',
                'correct' => true,
            ],
            [
                'accessToken' => $getAccessToken('http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'url' => 'https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'protocol' => 'https',
                'correct' => false,
            ],
        ];
    }
}
