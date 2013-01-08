<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http\Response;

use Imbo\Http\Response\Response,
    Imbo\Exception,
    Imbo\Exception\RuntimeException,
    ReflectionProperty;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\Http\Response\Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Response
     */
    private $response;

    private $headers;

    /**
     * Set up the response
     */
    public function setUp() {
        $this->headers = $this->getMock('Imbo\Http\HeaderContainer');
        $this->response = new Response($this->headers);
    }

    /**
     * Tear down the response
     */
    public function tearDown() {
        $this->headers = null;
        $this->response = null;
    }

    /**
     * @covers Imbo\Http\Response\Response::setStatusCode
     * @covers Imbo\Http\Response\Response::getStatusCode
     */
    public function testCanSetAndGetStatusCode() {
        $code = 404;
        $this->assertSame(200, $this->response->getStatusCode());
        $this->assertSame($this->response, $this->response->setStatusCode($code));
        $this->assertSame($code, $this->response->getStatusCode());
    }

    /**
     * @covers Imbo\Http\Response\Response::setStatusMessage
     * @covers Imbo\Http\Response\Response::getStatusMessage
     */
    public function testCanSetAndGetStatusMessage() {
        $message = 'some message';
        $this->assertSame($this->response, $this->response->setStatusMessage($message));
        $this->assertSame($message, $this->response->getStatusMessage());
    }

    /**
     * @covers Imbo\Http\Response\Response::getProtocolVersion
     * @covers Imbo\Http\Response\Response::setProtocolVersion
     */
    public function testCanSetAndGetProtocolVersion() {
        // Assert default version
        $this->assertSame('1.1', $this->response->getProtocolVersion());
        $this->assertSame($this->response, $this->response->setProtocolVersion('1.0'));
        $this->assertSame('1.0', $this->response->getProtocolVersion());
    }

    /**
     * @covers Imbo\Http\Response\Response::getHeaders
     * @covers Imbo\Http\Response\Response::setHeaders
     */
    public function testCanSetAndGetHeaders() {
        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $this->assertSame($this->response, $this->response->setHeaders($headers));
        $this->assertSame($headers, $this->response->getHeaders());
    }

    /**
     * @covers Imbo\Http\Response\Response::setBody
     * @covers Imbo\Http\Response\Response::getBody
     */
    public function testCanSetAndGetBody() {
        $body = 'some content';
        $this->assertSame($this->response, $this->response->setBody($body));
        $this->assertSame($body, $this->response->getBody());
    }

    /**
     * @covers Imbo\Http\Response\Response::setBody
     * @covers Imbo\Http\Response\Response::setStatusCode
     * @covers Imbo\Http\Response\Response::setNotModified
     * @covers Imbo\Http\Response\Response::getStatusCode
     * @covers Imbo\Http\Response\Response::getBody
     */
    public function testCanMarkItselfAsNotModified() {
        $this->assertSame($this->response, $this->response->setBody('some content'));
        $this->assertSame($this->response, $this->response->setStatusCode(200));

        $this->assertSame($this->response, $this->response->setNotModified());

        $this->assertSame(304, $this->response->getStatusCode());
        $this->assertEmpty($this->response->getBody());
    }

    /**
     * Return error codes and whether they are errors or not
     *
     * @return array[]
     */
    public function isErrorData() {
        return array(
            array(100, false),
            array(200, false),
            array(300, false),
            array(400, true),
            array(500, true),
        );
    }

    /**
     * @dataProvider isErrorData
     * @covers Imbo\Http\Response\Response::isError
     */
    public function testCanCheckIfTheStatusCodeIsAnErrorOrNot($code, $error) {
        $this->response->setStatusCode($code);
        $this->assertSame($error, $this->response->isError());
    }

    /**
     * @covers Imbo\Http\Response\Response::createError
     */
    public function testCanCreateAnErrorBasedOnAnException() {
        $this->headers->expects($this->at(0))->method('set')->with('X-Imbo-Error-Message', 'You wronged')->will($this->returnSelf());
        $this->headers->expects($this->at(1))->method('set')->with('X-Imbo-Error-InternalCode', 0)->will($this->returnSelf());
        $this->headers->expects($this->at(2))->method('set')->with('X-Imbo-Error-Date', $this->isType('string'))->will($this->returnSelf());
        $this->headers->expects($this->at(3))->method('remove')->with('ETag')->will($this->returnSelf());
        $this->headers->expects($this->at(4))->method('remove')->with('Last-Modified')->will($this->returnSelf());

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageIdentifier'));

        $exception = new RuntimeException('You wronged', 400);

        $this->response->createError($exception, $request);

        $this->assertSame(400, $this->response->getStatusCode());

        $body = $this->response->getBody();

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('code', $body['error']);
        $this->assertArrayHasKey('message', $body['error']);

        $this->assertSame(400, $body['error']['code']);
        $this->assertSame('You wronged', $body['error']['message']);
    }

    /**
     * @covers Imbo\Http\Response\Response::createError
     */
    public function testWillUseCorrectImageIdentifierFromRequestWhenCreatingError() {
        $this->headers->expects($this->at(0))->method('set')->with('X-Imbo-Error-Message', 'You wronged')->will($this->returnSelf());
        $this->headers->expects($this->at(1))->method('set')->with('X-Imbo-Error-InternalCode', 123)->will($this->returnSelf());
        $this->headers->expects($this->at(2))->method('set')->with('X-Imbo-Error-Date', $this->isType('string'))->will($this->returnSelf());
        $this->headers->expects($this->at(3))->method('remove')->with('ETag')->will($this->returnSelf());
        $this->headers->expects($this->at(4))->method('remove')->with('Last-Modified')->will($this->returnSelf());

        $exception = new RuntimeException('You wronged', 400);
        $exception->setImboErrorCode(123);

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->once())->method('getImage')->will($this->returnValue(null));
        $request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageIdentifier'));

        $this->response->createError($exception, $request);

        $body = $this->response->getBody();

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('imageIdentifier', $body);
        $this->assertArrayHasKey('imboErrorCode', $body['error']);

        $this->assertSame(123, $body['error']['imboErrorCode']);
        $this->assertSame('imageIdentifier', $body['imageIdentifier']);
    }

    /**
     * @covers Imbo\Http\Response\Response::createError
     */
    public function testWillUseImageChecksumAsImageIdentifierIfRequestHasAnImageWhenCreatingError() {
        $this->headers->expects($this->at(0))->method('set')->with('X-Imbo-Error-Message', 'You wronged')->will($this->returnSelf());
        $this->headers->expects($this->at(1))->method('set')->with('X-Imbo-Error-InternalCode', 123)->will($this->returnSelf());
        $this->headers->expects($this->at(2))->method('set')->with('X-Imbo-Error-Date', $this->isType('string'))->will($this->returnSelf());
        $this->headers->expects($this->at(3))->method('remove')->with('ETag')->will($this->returnSelf());
        $this->headers->expects($this->at(4))->method('remove')->with('Last-Modified')->will($this->returnSelf());

        $exception = new RuntimeException('You wronged', 400);
        $exception->setImboErrorCode(123);

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getChecksum')->will($this->returnValue('checksum'));
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $request->expects($this->never())->method('checksum');

        $this->response->createError($exception, $request);

        $body = $this->response->getBody();

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('imageIdentifier', $body);
        $this->assertArrayHasKey('imboErrorCode', $body['error']);

        $this->assertSame(123, $body['error']['imboErrorCode']);
        $this->assertSame('checksum', $body['imageIdentifier']);
    }

    /**
     * @covers Imbo\Http\Response\Response::createError
     */
    public function testWillNotSetBodyInErrorIfRequestMethodIsHead() {
        $this->headers->expects($this->at(0))->method('set')->with('X-Imbo-Error-Message', 'You wronged')->will($this->returnSelf());
        $this->headers->expects($this->at(1))->method('set')->with('X-Imbo-Error-InternalCode', 0)->will($this->returnSelf());
        $this->headers->expects($this->at(2))->method('set')->with('X-Imbo-Error-Date', $this->isType('string'))->will($this->returnSelf());
        $this->headers->expects($this->at(3))->method('remove')->with('ETag')->will($this->returnSelf());
        $this->headers->expects($this->at(4))->method('remove')->with('Last-Modified')->will($this->returnSelf());

        $exception = new RuntimeException('You wronged', 400);

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('HEAD'));

        $this->response->createError($exception, $request);

        $this->assertNull($this->response->getBody());
    }

    /**
     * @covers Imbo\Http\Response\Response::getDefinition
     */
    public function testReturnsACorrectDefinition() {
        $definition = $this->response->getDefinition();
        $this->assertInternalType('array', $definition);

        foreach ($definition as $d) {
            $this->assertInstanceOf('Imbo\EventListener\ListenerDefinition', $d);
        }
    }

    /**
     * @covers Imbo\Http\Response\Response::setImage
     * @covers Imbo\Http\Response\Response::getImage
     */
    public function testCanSetAndGetImage() {
        $image = $this->getMock('Imbo\Image\Image');
        $this->assertSame($this->response, $this->response->setImage($image));
        $this->assertSame($image, $this->response->getImage());
    }

    /**
     * Get different last modified date combos
     *
     * @return array[]
     */
    public function getLastModifiedData() {
        return array(
            array(null),
            array('Mon, 10 Dec 2012 11:57:51 GMT'),
        );
    }

    /**
     * @dataProvider getLastModifiedData
     * @covers Imbo\Http\Response\Response::getLastModified
     */
    public function testCanReturnTheLastModifiedHeader($lastModified) {
        $this->headers->expects($this->once())->method('get')->with('Last-Modified')->will($this->returnValue($lastModified));
        $this->assertSame($lastModified, $this->response->getLastModified());
    }

    /**
     * @covers Imbo\Http\Response\Response::__construct
     * @covers Imbo\Http\Response\Response::getHeaders
     */
    public function testCanCreateAHeaderContainerByItself() {
        $response = new Response();
        $this->assertInstanceOf('Imbo\Http\HeaderContainer', $response->getHeaders());
    }

    /**
     * @covers Imbo\Http\Response\Response::send
     * @covers Imbo\Http\Response\Response::sendHeaders
     */
    public function testCanSendHeadersAndContent() {
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageIdentifier'));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $this->headers->expects($this->at(0))->method('get')->with('last-modified')->will($this->returnValue(null));
        $this->headers->expects($this->at(1))->method('get')->with('etag')->will($this->returnValue(null));
        $this->headers->expects($this->at(2))->method('set')->with('X-Imbo-ImageIdentifier', 'imageIdentifier');

        $this->expectOutputString('{"foo":"bar"}');
        $this->response->setBody(array('foo' => 'bar'))->send($event);
    }

    /**
     * @covers Imbo\Http\Response\Response::send
     * @covers Imbo\Http\Response\Response::sendHeaders
     */
    public function testCanSendHeadersAndContentUsingImageInstanceForImageIdentifier() {
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getChecksum')->will($this->returnValue('checksum'));

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $this->headers->expects($this->at(0))->method('get')->with('last-modified')->will($this->returnValue(null));
        $this->headers->expects($this->at(1))->method('get')->with('etag')->will($this->returnValue(null));
        $this->headers->expects($this->at(2))->method('set')->with('X-Imbo-ImageIdentifier', 'checksum');

        $this->expectOutputString('{"foo":"bar"}');
        $this->response->setBody(array('foo' => 'bar'))->send($event);
    }

    /**
     * @covers Imbo\Http\Response\Response::send
     * @covers Imbo\Http\Response\Response::sendHeaders
     */
    public function testSupports304NotModified() {
        $lastModified = 'Mon, 10 Dec 2012 11:57:51 GMT';
        $etag = '"tag"';

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->at(0))->method('get')->with('if-modified-since')->will($this->returnValue($lastModified));
        $requestHeaders->expects($this->at(1))->method('get')->with('if-none-match')->will($this->returnValue($etag));

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $this->headers->expects($this->at(0))->method('get')->with('last-modified')->will($this->returnValue($lastModified));
        $this->headers->expects($this->at(1))->method('get')->with('etag')->will($this->returnValue($etag));
        $this->headers->expects($this->at(2))->method('remove')->with('Allow');
        $this->headers->expects($this->at(3))->method('remove')->with('Content-Encoding');
        $this->headers->expects($this->at(4))->method('remove')->with('Content-Language');
        $this->headers->expects($this->at(5))->method('remove')->with('Content-Length');
        $this->headers->expects($this->at(6))->method('remove')->with('Content-MD5');
        $this->headers->expects($this->at(7))->method('remove')->with('Last-Modified');

        $this->response->send($event);
    }
}
