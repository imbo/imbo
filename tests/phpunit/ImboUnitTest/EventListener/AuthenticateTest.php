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

use Imbo\EventListener\Authenticate;

/**
 * @covers Imbo\EventListener\Authenticate
 * @group unit
 * @group listeners
 */
class AuthenticateTest extends ListenerTests {
    /**
     * @var Authenticate
     */
    private $listener;

    private $event;
    private $accessControl;
    private $request;
    private $response;
    private $query;
    private $headers;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->query = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $this->headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->accessControl = $this->getMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface');

        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->request->query = $this->query;
        $this->request->headers = $this->headers;

        $this->response = $this->getMock('Imbo\Http\Response\Response');

        $this->event = $this->getEventMock();

        $this->listener = new Authenticate();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    protected function getEventMock($config = null) {
        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $event->expects($this->any())->method('getAccessControl')->will($this->returnValue($this->accessControl));
        $event->expects($this->any())->method('getConfig')->will($this->returnValue($config ?: [
            'authentication' => [
                'protocol' => 'incoming'
            ]
        ]));
        return $event;
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->query = null;
        $this->headers = null;
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\Authenticate::authenticate
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Missing authentication timestamp
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenAuthInfoIsMissing() {
        $this->headers->expects($this->at(0))->method('has')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(false));
        $this->headers->expects($this->at(1))->method('get')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(null));

        $this->listener->authenticate($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::authenticate
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Missing authentication signature
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenSignatureIsMissing() {
        $this->headers->expects($this->at(0))->method('has')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(true));
        $this->headers->expects($this->at(1))->method('has')->with('x-imbo-authenticate-signature')->will($this->returnValue(true));
        $this->headers->expects($this->at(2))->method('get')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(gmdate('Y-m-d\TH:i:s\Z')));

        $this->listener->authenticate($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::authenticate
     * @covers Imbo\EventListener\Authenticate::timestampIsValid
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Invalid timestamp: some string
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenTimestampIsInvalid() {
        $this->headers->expects($this->at(0))->method('has')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(true));
        $this->headers->expects($this->at(1))->method('has')->with('x-imbo-authenticate-signature')->will($this->returnValue(true));
        $this->headers->expects($this->at(2))->method('get')->with('x-imbo-authenticate-timestamp')->will($this->returnValue('some string'));

        $this->listener->authenticate($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::authenticate
     * @covers Imbo\EventListener\Authenticate::timestampHasExpired
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Timestamp has expired: 2010-10-10T20:10:10Z
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenTimestampHasExpired() {
        $this->headers->expects($this->at(0))->method('has')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(true));
        $this->headers->expects($this->at(1))->method('has')->with('x-imbo-authenticate-signature')->will($this->returnValue(true));
        $this->headers->expects($this->at(2))->method('get')->with('x-imbo-authenticate-timestamp')->will($this->returnValue('2010-10-10T20:10:10Z'));

        $this->listener->authenticate($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::authenticate
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Signature mismatch
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenSignatureDoesNotMatch() {
        $this->headers->expects($this->at(0))->method('has')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(true));
        $this->headers->expects($this->at(1))->method('has')->with('x-imbo-authenticate-signature')->will($this->returnValue(true));
        $this->headers->expects($this->at(2))->method('get')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(gmdate('Y-m-d\TH:i:s\Z')));
        $this->headers->expects($this->at(3))->method('get')->with('x-imbo-authenticate-signature')->will($this->returnValue('foobar'));

        $this->listener->authenticate($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::authenticate
     * @covers Imbo\EventListener\Authenticate::signatureIsValid
     * @covers Imbo\EventListener\Authenticate::timestampIsValid
     * @covers Imbo\EventListener\Authenticate::timestampHasExpired
     */
    public function testApprovesValidSignature() {
        $httpMethod = 'GET';
        $url = 'http://imbo/users/christer/images/image';
        $publicKey = 'christer';
        $privateKey = 'key';
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $data = $httpMethod . '|' . $url . '|' . $publicKey . '|' . $timestamp;
        $signature = hash_hmac('sha256', $data, $privateKey);

        $this->accessControl->expects($this->once())->method('getPrivateKey')->will($this->returnValue($privateKey));

        $this->headers->expects($this->at(0))->method('has')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(true));
        $this->headers->expects($this->at(1))->method('has')->with('x-imbo-authenticate-signature')->will($this->returnValue(true));
        $this->headers->expects($this->at(2))->method('get')->with('x-imbo-authenticate-timestamp')->will($this->returnValue($timestamp));
        $this->headers->expects($this->at(3))->method('get')->with('x-imbo-authenticate-signature')->will($this->returnValue($signature));

        $this->request->expects($this->once())->method('getRawUri')->will($this->returnValue($url));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue($httpMethod));

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-AuthUrl', $url);

        $this->response->headers = $responseHeaders;

        $this->listener->authenticate($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::authenticate
     * @covers Imbo\EventListener\Authenticate::signatureIsValid
     * @covers Imbo\EventListener\Authenticate::timestampIsValid
     * @covers Imbo\EventListener\Authenticate::timestampHasExpired
     */
    public function testApprovesValidSignatureWithAuthInfoFromQueryParameters() {
        $httpMethod = 'GET';
        $url = 'http://imbo/users/christer/images/image';
        $publicKey = 'christer';
        $privateKey = 'key';
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $data = $httpMethod . '|' . $url . '|' . $publicKey . '|' . $timestamp;
        $signature = hash_hmac('sha256', $data, $privateKey);
        $rawUrl = $url . '?signature=' . $signature . '&timestamp=' . $timestamp;

        $this->accessControl->expects($this->once())->method('getPrivateKey')->will($this->returnValue($privateKey));

        $this->headers->expects($this->at(0))->method('has')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(false));
        $this->headers->expects($this->at(1))->method('get')->with('x-imbo-authenticate-timestamp', $timestamp)->will($this->returnValue($timestamp));
        $this->headers->expects($this->at(2))->method('get')->with('x-imbo-authenticate-signature', $signature)->will($this->returnValue($signature));
        $this->query->expects($this->at(0))->method('get')->with('timestamp')->will($this->returnValue($timestamp));
        $this->query->expects($this->at(1))->method('get')->with('signature')->will($this->returnValue($signature));

        $this->request->expects($this->once())->method('getRawUri')->will($this->returnValue($rawUrl));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue($httpMethod));

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-AuthUrl', $url);

        $this->response->headers = $responseHeaders;

        $this->listener->authenticate($this->event);
    }

    /**
     * Get signatures with rewritten URLs
     *
     * @return array[]
     */
    public function getRewrittenSignatureData() {
        return array_map(function($dataSet) {
            $httpMethod = 'PUT';
            $publicKey = 'christer';
            $privateKey = 'key';
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $data = $httpMethod . '|' . $dataSet[0] . '|' . $publicKey . '|' . $timestamp;
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
     * @covers Imbo\EventListener\Authenticate::authenticate
     * @covers Imbo\EventListener\Authenticate::signatureIsValid
     * @covers Imbo\EventListener\Authenticate::timestampIsValid
     * @covers Imbo\EventListener\Authenticate::timestampHasExpired
     */
    public function testApprovesSignaturesWhenConfigurationForcesProtocol($serverUrl, $protocol, $authHeader, $shouldMatch, $signature, $timestamp) {
        if (!$shouldMatch) {
            $this->setExpectedException('Imbo\Exception\RuntimeException', 'Signature mismatch', 400);
        }

        $this->accessControl->expects($this->once())->method('getPrivateKey')->will($this->returnValue('key'));

        $this->headers->expects($this->at(0))->method('has')->with('x-imbo-authenticate-timestamp')->will($this->returnValue(false));
        $this->headers->expects($this->at(1))->method('get')->with('x-imbo-authenticate-timestamp', $timestamp)->will($this->returnValue($timestamp));
        $this->headers->expects($this->at(2))->method('get')->with('x-imbo-authenticate-signature', $signature)->will($this->returnValue($signature));
        $this->query->expects($this->at(0))->method('get')->with('timestamp')->will($this->returnValue($timestamp));
        $this->query->expects($this->at(1))->method('get')->with('signature')->will($this->returnValue($signature));

        $this->request->expects($this->once())->method('getRawUri')->will($this->returnValue($serverUrl));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue('christer'));
        $this->request->expects($this->any())->method('getMethod')->will($this->returnValue('PUT'));

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-AuthUrl', $authHeader);

        $this->response->headers = $responseHeaders;

        $this->listener->authenticate($this->getEventMock([
            'authentication' => [
                'protocol' => $protocol,
            ]
        ]));
    }
}
