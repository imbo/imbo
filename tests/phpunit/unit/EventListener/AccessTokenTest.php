<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener;

use Imbo\EventListener\AccessToken;
use Imbo\EventListener\AccessToken\AccessTokenInterface;
use Imbo\Exception\RuntimeException;
use Imbo\Exception\ConfigurationException;
use Imbo\EventManager\Event;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface as AccessControlAdapter;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use stdClass;

/**
 * @covers Imbo\EventListener\AccessToken
 * @coversDefaultClass Imbo\EventListener\AccessToken
 * @group unit
 * @group listeners
 */
class AccessTokenTest extends ListenerTests {
    /**
     * @var AccessToken
     */
    private $listener;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var AccessControlAdapter
     */
    private $accessControl;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var ResponseHeaderBag
     */
    private $responseHeaders;

    /**
     * @var ParameterBag
     */
    private $query;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->query = $this->createMock(ParameterBag::class);

        $this->accessControl = $this->createMock(AccessControlAdapter::class);

        $this->request = $this->createMock(Request::class);
        $this->request->query = $this->query;

        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->response = $this->createMock(Response::class);
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->getEventMock();

        $this->listener = new AccessToken();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers ::checkAccessToken
     */
    public function testRequestWithBogusAccessToken() {
        $this->query->expects($this->once())
                    ->method('has')
                    ->with('accessToken')
                    ->willReturn(true);

        $this->query->expects($this->once())
                    ->method('get')
                    ->with('accessToken')
                    ->willReturn('/string');

        $this->expectExceptionObject(new RuntimeException('Incorrect access token', 400));
        $this->listener->checkAccessToken($this->event);
    }

    /**
     * @covers ::checkAccessToken
     * @covers ::isWhitelisted
     */
    public function testThrowsExceptionIfAnAccessTokenIsMissingFromTheRequestWhenNotWhitelisted() {
        $this->event->expects($this->once())
                    ->method('getName')
                    ->willReturn('image.get');

        $this->query->expects($this->once())
                    ->method('has')
                    ->with('accessToken')
                    ->willReturn(false);

        $this->expectExceptionObject(new RuntimeException('Missing access token', 400));
        $this->listener->checkAccessToken($this->event);
    }

    /**
     * Different filter combinations
     *
     * @return array[]
     */
    public function getFilterData() {
        return [
            'no filter and no transformations' => [
                $filter = [],
                $transformations = [],
                $whitelisted = false,
            ],
            // @see https://github.com/imbo/imbo/issues/258
            'configured filters, but no transformations in the request' => [
                $filter = ['transformations' => ['whitelist' => ['border']]],
                $transformations = [],
                $whitelisted = false,
            ],
            [
                $filter = [],
                $transformations = [
                    ['name' => 'border', 'params' => []],
                ],
                $whitelisted = false,
            ],
            [
                $filter = ['transformations' => ['whitelist' => ['border']]],
                $transformations = [
                    ['name' => 'border', 'params' => []],
                ],
                $whitelisted = true,
            ],
            [
                $filter = ['transformations' => ['whitelist' => ['border']]],
                $transformations = [
                    ['name' => 'border', 'params' => []],
                    ['name' => 'thumbnail', 'params' => []],
                ],
                $whitelisted = false,
            ],
            [
                $filter = ['transformations' => ['blacklist' => ['border']]],
                $transformations = [
                    ['name' => 'thumbnail', 'params' => []],
                ],
                $whitelisted = true,
            ],
            [
                $filter = ['transformations' => ['blacklist' => ['border']]],
                $transformations = [
                    ['name' => 'border', 'params' => []],
                    ['name' => 'thumbnail', 'params' => []],
                ],
                $whitelisted = false,
            ],
            [
                $filter = ['transformations' => ['whitelist' => ['border'], 'blacklist' => ['thumbnail']]],
                $transformations = [
                    ['name' => 'border', 'params' => []],
                ],
                $whitelisted = true,
            ],
            [
                $filter = ['transformations' => ['whitelist' => ['border'], 'blacklist' => ['thumbnail']]],
                $transformations = [
                    ['name' => 'canvas', 'params' => []],
                ],
                $whitelisted = false,
            ],
            [
                $filter = ['transformations' => ['whitelist' => ['border'], 'blacklist' => ['border']]],
                $transformations = [
                    ['name' => 'border', 'params' => []],
                ],
                $whitelisted = false,
            ],
        ];
    }

    /**
     * @dataProvider getFilterData
     * @covers ::__construct
     * @covers ::checkAccessToken
     * @covers ::isWhitelisted
     */
    public function testSupportsFilters($filter, $transformations, $whitelisted) {
        $listener = new AccessToken($filter);

        if (!$whitelisted) {
            $this->expectExceptionObject(new RuntimeException('Missing access token', 400));
        }

        $this->event->expects($this->once())
                    ->method('getName')
                    ->willReturn('image.get');

        $this->request->method('getTransformations')
                      ->willReturn($transformations);

        $listener->checkAccessToken($this->event);
    }

    /**
     * Get access tokens
     *
     * @return array[]
     */
    public function getAccessTokens() {
        return [
            [
                'http://imbo/users/christer',
                'some access token',
                'private key',
                false
            ],
            [
                'http://imbo/users/christer',
                '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a',
                'private key',
                true
            ],
            [
                'http://imbo/users/christer',
                '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a',
                'other private key',
                false
            ],
            // Test that checking URL "as is" works properly. This is for backwards compatibility.
            [
                'http://imbo/users/christer?t[]=thumbnail%3Awidth%3D40%2Cheight%3D40%2Cfit%3Doutbound',
                'f0166cb4f7c8eabbe82c5d753f681ed53bcfa10391d4966afcfff5806cc2bff4',
                'some random private key',
                true
            ],
            // Test checking URL against incorrect protocol
            [
                'https://imbo/users/christer',
                '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a',
                'private key',
                false
            ],
            // Test that URLs with t[0] and t[] can use the same accessToken
            [
                'http://imbo/users/imbo/images/foobar?t%5B0%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B1%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50',
                '1ae7643a70e68377502c30ba54d0ffbfedd67a1f3c4b3f038a42c0ed17ad3551',
                'foo',
                true
            ],
            [
                'http://imbo/users/imbo/images/foobar?t%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50',
                '1ae7643a70e68377502c30ba54d0ffbfedd67a1f3c4b3f038a42c0ed17ad3551',
                'foo',
                true
            ],
            // and that they still break if something else is changed
            [
                'http://imbo/users/imbo/images/foobar?g%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50',
                '1ae7643a70e68377502c30ba54d0ffbfedd67a1f3c4b3f038a42c0ed17ad3551',
                'foo',
                false
            ],
        ];
    }

    /**
     * @dataProvider getAccessTokens
     * @covers ::checkAccessToken
     */
    public function testThrowsExceptionOnIncorrectToken($url, $token, $privateKey, $correct) {
        if (!$correct) {
            $this->expectExceptionObject(new RuntimeException('Incorrect access token', 400));
        }

        $this->query->expects($this->once())
                    ->method('has')
                    ->with('accessToken')
                    ->willReturn(true);

        $this->query->expects($this->once())
                    ->method('get')
                    ->with('accessToken')
                    ->willReturn($token);

        $this->request->expects($this->atLeastOnce())
                      ->method('getRawUri')
                      ->willReturn(urldecode($url));

        $this->request->expects($this->atLeastOnce())
                      ->method('getUriAsIs')
                      ->willReturn($url);

        $this->accessControl->expects($this->once())
                            ->method('getPrivateKey')
                            ->willReturn($privateKey);

        $this->listener->checkAccessToken($this->event);
    }

    /**
     * @covers ::checkAccessToken
     */
    public function testWillSkipValidationWhenShortUrlHeaderIsPresent() {
        $this->responseHeaders->expects($this->once())
                              ->method('has')
                              ->with('X-Imbo-ShortUrl')
                              ->willReturn(true);

        $this->query->expects($this->never())
                    ->method('has');

        $this->listener->checkAccessToken($this->event);
    }

    /**
     * @covers ::checkAccessToken
     */
    public function testWillAcceptBothProtocolsIfConfiguredTo() {
        $privateKey = 'foobar';
        $baseUrl = '//imbo.host/users/some-user/imgUrl.png?t[]=smartSize:width=320,height=240';

        $this->accessControl->method('getPrivateKey')
                            ->willReturn($privateKey);

        foreach (['http', 'https'] as $signedProtocol) {
            $token = hash_hmac('sha256', $signedProtocol . ':' . $baseUrl, $privateKey);

            $query = $this->createMock(ParameterBag::class);
            $query->method('has')
                  ->with('accessToken')
                  ->willReturn(true);

            $query->method('get')
                  ->with('accessToken')
                  ->willReturn($token);

            foreach (['http', 'https'] as $protocol) {
                $url = $protocol . ':' . $baseUrl . '&accessToken=' . $token;

                $request = $this->createConfiguredMock(Request::class, [
                    'getRawUri' => urldecode($url),
                    'getUriAsIs' => $url,
                ]);
                $request->query = $query;

                $event = $this->createConfiguredMock(Event::class, [
                    'getAccessControl' => $this->accessControl,
                    'getRequest' => $request,
                    'getResponse' => $this->response,
                    'getConfig' => [
                        'authentication' => [
                            'protocol' => 'both'
                        ],
                    ],
                ]);

                $this->assertTrue(
                    $this->listener->checkAccessToken($event),
                    'Expected method to return true to signal successful comparison'
                );
            }
        }
    }

    /**
     * Test that we can configure the access token argument key
     */
    public function testAccessTokenArgumentKey() {
        $url = 'http://imbo/users/christer';
        $token = '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a';
        $privateKey = 'private key';

        $listener = new AccessToken([
            'accessTokenGenerator' => new AccessToken\SHA256(['argumentKeys' => ['foo']]),
        ]);

        $this->query->expects($this->once())
                    ->method('has')
                    ->with('foo')
                    ->willReturn(true);

        $this->query->expects($this->once())
                    ->method('get')
                    ->with('foo')
                    ->willReturn($token);

        $this->request->expects($this->atLeastOnce())
                      ->method('getRawUri')
                      ->willReturn(urldecode($url));

        $this->request->expects($this->atLeastOnce())
                      ->method('getUriAsIs')
                      ->willReturn($url);

        $this->accessControl->expects($this->once())
                            ->method('getPrivateKey')
                            ->willReturn($privateKey);

        $listener->checkAccessToken($this->event);
    }

    public function getAccessTokensForMultipleGenerator() {
        $tokens = array();

        foreach ($this->getAccessTokens() as $token) {
            $token[] = 'accessToken';
            $tokens[] = $token;
        }

        $tokens = array_merge($tokens, [
            [
                'http://imbo/users/imbo/images/foobar?t%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50',
                'dummy',
                'foo',
                true,
                'dummy'
            ],
            [
                'http://imbo/users/imbo/images/foobar?t%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50123',
                'dummy',
                'foobar',
                true,
                'dummy'
            ],
            [
                'http://imbo/users/imbo/images/foobar?t%5B%5D=maxSize%3Awidth%3D400%2Cheight%3D400&t%5B%5D=crop%3Ax%3D50%2Cy%3D50%2Cwidth%3D50%2Cheight%3D50123',
                'boop',
                'foobar',
                false,
                'dummy'
            ],
        ]);

        return $tokens;
    }

    /**
     * Test using the multiple access token generators generator
     *
     * @dataProvider getAccessTokensForMultipleGenerator
     */
    public function testMultipleAccessTokensGenerator($url, $token, $privateKey, $correct, $argumentKey) {
        if (!$correct) {
            $this->expectExceptionObject(new RuntimeException('Incorrect access token', 400));
        }

        $dummyAccessToken = $this->createConfiguredMock(AccessTokenInterface::class, [
            'generateSignature' => 'dummy',
        ]);

        $listener = new AccessToken([
            'accessTokenGenerator' => new AccessToken\MultipleAccessTokenGenerators([
                    'generators' => [
                        'accessToken' => new AccessToken\SHA256(),
                        'dummy' => $dummyAccessToken,
                    ]
                ]
            ),
        ]);

        // Allows us to return 'false' as default if the key isn't present
        $this->query->expects($this->atLeastOnce())
                    ->method('has')
                    ->with($this->logicalOr(
                        $this->equalTo($argumentKey),
                        $this->anything()
                    ))
                    ->will($this->returnCallback(function ($val) use ($argumentKey) {
                        return $val == $argumentKey;
                    }));

        $this->query->expects($this->atLeastOnce())
                    ->method('get')
                    ->with($argumentKey)
                    ->willReturn($token);

        $this->request->expects($this->atLeastOnce())
                      ->method('getRawUri')
                      ->willReturn(urldecode($url));

        $this->request->expects($this->atLeastOnce())
                      ->method('getUriAsIs')
                      ->willReturn($url);

        $this->accessControl->expects($this->once())
                            ->method('getPrivateKey')
                            ->willReturn($privateKey);

        $listener->checkAccessToken($this->event);
    }

    /**
     * Test that we can configure the access token argument key
     */
    public function testConfigurationExceptionOnInvalidAccessTokenGenerator() {
        $this->expectExceptionObject(new ConfigurationException('Invalid accessTokenGenerator', 500));

        new AccessToken(['accessTokenGenerator' => new StdClass()]);
    }

    /**
     * Get access tokens with rewritten URLs
     *
     * @return array[]
     */
    public function getRewrittenAccessTokenData() {
        $getAccessToken = function($url) {
            return hash_hmac('sha256', $url, 'foobar');
        };

        return [
            [
                // Access token created from URL
                $getAccessToken('http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                 // URL returned by Imbo
                'http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                // Protocol which Imbo is configured to rewrite incoming URL to
                'http',
                // Should it accept the access token as correct?
                true
            ],
            [
                $getAccessToken('http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'http',
                true
            ],
            [
                $getAccessToken('https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'http',
                false
            ],
            [
                $getAccessToken('https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'http',
                false
            ],
            [
                $getAccessToken('https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'https',
                true
            ],
            [
                $getAccessToken('https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'https',
                true
            ],
            [
                $getAccessToken('http://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240'),
                'https://imbo/users/espen/img.png?t[]=smartSize:width=320,height=240',
                'https',
                false
            ],
        ];
    }

    /**
     * @dataProvider getRewrittenAccessTokenData
     * @covers ::checkAccessToken
     */
    public function testWillRewriteIncomingUrlToConfiguredProtocol($accessToken, $url, $protocol, $correct) {
        if (!$correct) {
            $this->expectExceptionObject(new RuntimeException('Incorrect access token', 400));
        }

        $event = $this->getEventMock([
            'authentication' => [
                'protocol' => $protocol
            ]
        ]);

        $url = $url . '&accessToken=' . $accessToken;

        $this->query->method('has')
                    ->with('accessToken')
                    ->willReturn(true);

        $this->query->method('get')
                    ->with('accessToken')
                    ->willReturn($accessToken);

        $this->request->method('getRawUri')
                      ->willReturn(urldecode($url));

        $this->request->method('getUriAsIs')
                      ->willReturn($url);

        $this->accessControl->method('getPrivateKey')
                            ->willReturn('foobar');

        $this->assertTrue(
            $this->listener->checkAccessToken($event),
            'Expected method to return true to signal successful comparison'
        );
    }

    /**
     * Get an event mock
     *
     * @param array $config Configuration to be returned from the getConfig method
     */
    protected function getEventMock(array $config = null) {
        return $this->createConfiguredMock(Event::class, [
            'getAccessControl' => $this->accessControl,
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getConfig' => $config ?: [
                'authentication' => [
                    'protocol' => 'incoming'
                ],
            ],
        ]);
    }
}
