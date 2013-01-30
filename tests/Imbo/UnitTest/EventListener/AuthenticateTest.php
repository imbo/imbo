<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\Authenticate;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class AuthenticateTest extends ListenerTests {
    /**
     * @var Authenticate
     */
    private $listener;

    private $event;
    private $request;
    private $response;
    private $query;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->query = $this->getMockBuilder('Imbo\Http\ParameterContainer')->disableOriginalConstructor()->getMock();

        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getQuery')->will($this->returnValue($this->query));

        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->listener = new Authenticate();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->query = null;
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\Authenticate::invoke
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Missing required authentication parameter: signature
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenSignatureIsMissing() {
        $this->query->expects($this->any())->method('has')->with('signature')->will($this->returnValue(false));
        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::invoke
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Missing required authentication parameter: timestamp
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenTimestampIsMissing() {
        $this->query->expects($this->any())->method('has')->will($this->returnCallback(function($arg) {
            if ($arg === 'signature') {
                return true;
            }

            return false;
        }));

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::invoke
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Invalid timestamp: some string
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenTimestampIsInvalid() {
        $this->query->expects($this->any())->method('has')->will($this->returnValue(true));
        $this->query->expects($this->any())->method('get')->will($this->returnCallback(function($arg) {
            if ($arg === 'timestamp') {
                return 'some string';
            }

            return 'signature';
        }));

        $this->query->expects($this->any())->method('remove')->will($this->returnSelf());

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::invoke
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Timestamp has expired: 2010-10-10T20:10:10Z
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenTimestampHasExpired() {
        $this->query->expects($this->any())->method('has')->will($this->returnValue(true));
        $this->query->expects($this->any())->method('get')->will($this->returnCallback(function($arg) {
            if ($arg === 'timestamp') {
                return '2010-10-10T20:10:10Z';
            }

            return 'signature';
        }));

        $this->query->expects($this->any())->method('remove')->will($this->returnSelf());

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::invoke
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Signature mismatch
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenSignatureDoesNotMatch() {
        $this->query->expects($this->any())->method('has')->will($this->returnValue(true));
        $this->query->expects($this->any())->method('remove')->will($this->returnSelf());
        $this->query->expects($this->any())->method('get')->will($this->returnCallback(function($arg) {
            if ($arg === 'timestamp') {
                return gmdate('Y-m-d\TH:i:s\Z');
            }

            return 'signature';
        }));

        $this->request->expects($this->once())->method('getUrl')->will($this->returnValue('http://imbo/users/christer'));
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-AuthUrl', 'http://imbo/users/christer');
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Authenticate::invoke
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

        $this->query->expects($this->any())->method('has')->will($this->returnValue(true));
        $this->query->expects($this->any())->method('remove')->will($this->returnSelf());
        $this->query->expects($this->any())->method('get')->will($this->returnCallback(function($arg) use ($timestamp, $signature) {
            if ($arg === 'timestamp') {
                return $timestamp;
            }

            return $signature;
        }));

        $this->request->expects($this->once())->method('getUrl')->will($this->returnValue($url));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getPrivateKey')->will($this->returnValue($privateKey));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue($httpMethod));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('X-Imbo-AuthUrl', $url);

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->listener->invoke($this->event);
    }
}
