<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Request;

use PHPIMS\Image\TransformationChain;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class RequestTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException PHPIMS\Request\Exception
     * @expectedExceptionMessage Unsupported HTTP method: TRACE
     * @expectedExceptionCode 400
     */
    public function testRequestWithInvalidMethod() {
        $request = new Request('TRACE', '', array());
    }

    /**
     * @expectedException PHPIMS\Request\Exception
     * @expectedExceptionMessage Unknown resource: /some/resource
     * @expectedExceptionCode 400
     */
    public function testRequestWithInvalidResource() {
        $request = new Request('GET', '/some/resource', array());
    }

    /**
     * @expectedException PHPIMS\Request\Exception
     * @expectedExceptionMessage Unknown public key
     * @expectedExceptionCode 400
     */
    public function testRequestWithUnknownPublicKey() {
        $publicKey = md5(microtime());
        $privateKey = md5(microtime());

        $authConfig = array(
            $publicKey => $privateKey,
        );
        $request = new Request('GET', '/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/images', $authConfig);
    }

    public function testImageRequest() {
        $publicKey = md5(microtime());
        $privateKey = md5(microtime());
        $imageIdentifier = md5(microtime()) . '.png';

        $authConfig = array(
            $publicKey => $privateKey,
        );
        $request = new Request('GET', '/' . $publicKey . '/' . $imageIdentifier, $authConfig);
        $this->assertSame($imageIdentifier, $request->getImageIdentifier());
        $this->assertTrue($request->isImageRequest());
        $this->assertFalse($request->isImagesRequest());
        $this->assertFalse($request->isMetadataRequest());
        $this->assertSame($imageIdentifier, $request->getResource());
        $this->assertSame('GET', $request->getMethod());

        $this->assertSame($publicKey, $request->getPublicKey());
        $this->assertSame($privateKey, $request->getPrivateKey());
    }

    public function testImagesRequest() {
        $publicKey = md5(microtime());
        $privateKey = md5(microtime());

        $authConfig = array(
            $publicKey => $privateKey,
        );
        $request = new Request('GET', '/' . $publicKey . '/images', $authConfig);
        $this->assertFalse($request->isImageRequest());
        $this->assertTrue($request->isImagesRequest());
        $this->assertFalse($request->isMetadataRequest());
    }

    public function testMetadataRequest() {
        $publicKey = md5(microtime());
        $privateKey = md5(microtime());
        $imageIdentifier = md5(microtime()) . '.png';

        $authConfig = array(
            $publicKey => $privateKey,
        );
        $request = new Request('GET', '/' . $publicKey . '/' . $imageIdentifier . '/meta', $authConfig);
        $this->assertSame($imageIdentifier, $request->getImageIdentifier());
        $this->assertFalse($request->isImageRequest());
        $this->assertFalse($request->isImagesRequest());
        $this->assertTrue($request->isMetadataRequest());
    }

    private function getRequest() {
        $publicKey = md5(microtime());
        $privateKey = md5(microtime());
        $imageIdentifier = md5(microtime()) . '.png';
        $authConfig = array(
            $publicKey => $privateKey,
        );
        return new Request('GET', '/' . $publicKey . '/' . $imageIdentifier, $authConfig);
    }

    public function testGetTimestamp() {
        $request = $this->getRequest();

        $this->assertNull($request->getTimestamp());
        $_GET['timestamp'] = time();
        $this->assertSame($_GET['timestamp'], $request->getTimestamp());
    }

    public function testGetSignature() {
        $request = $this->getRequest();

        $this->assertNull($request->getSignature());
        $_GET['signature'] = time();
        $this->assertSame($_GET['signature'], $request->getSignature());
    }

    public function testGetMetadata() {
        $request = $this->getRequest();

        $metadata = array(
            'somekey' => 'value',
            'someOtherKey' => 'someOtherValue',
        );

        $this->assertNull($request->getMetadata());
        $_POST['metadata'] = json_encode($metadata);
        $this->assertSame($metadata, $request->getMetadata());
    }

    public function testGetMetadataWhenDataIsNotValidJson() {
        $request = $this->getRequest();

        $_POST['metadata'] = 'some data';
        $this->assertNull($request->getMetadata());
    }

    public function testGetPost() {
        $request = $this->getRequest();
        $this->assertNull($request->getPost('foo'));
        $_POST['foo'] = 123;
        $this->assertSame(123, $request->getPost('foo'));
    }

    public function testHasPost() {
        $request = $this->getRequest();
        $this->assertFalse($request->hasPost('foo'));
        $_POST['foo'] = 'foobar';
        $this->assertTrue($request->hasPost('foo'));
    }

    public function testGet() {
        $request = $this->getRequest();
        $this->assertNull($request->get('foo'));
        $_GET['foo'] = 123;
        $this->assertSame(123, $request->get('foo'));
    }

    public function testHas() {
        $request = $this->getRequest();
        $this->assertFalse($request->has('foo'));
        $_GET['foo'] = 'foobar';
        $this->assertTrue($request->has('foo'));
    }

    public function testGetTransformationsWithNoTransformationsPresent() {
        $request = $this->getRequest();
        $this->assertEquals(new TransformationChain(), $request->getTransformations());
    }

    public function testGetTransformations() {
        $request = $this->getRequest();
        $_GET['t'] = array(
            // Valid transformations with all options
            'border:color=fff,width=2,height=2',
            'compress:quality=90',
            'crop:x=1,y=2,width=3,height=4',
            'resize:width=100,height=100',
            'rotate:angle=45,bg=fff',
            'thumbnail:width=100,height=100,fit=outbound',

            // Transformations with no options
            'flipHorizontally',
            'flipVertically',

            // Invalid transformations
            'foo',
            'bar:some=option',
        );

        $chain = $request->getTransformations();
        $this->assertInstanceOf('PHPIMS\Image\TransformationChain', $chain);
    }

    public function testSetGetImageIdentifier() {
        $request = $this->getRequest();
        $identifier = md5(microtime()) . '.png';
        $request->setImageIdentifier($identifier);
        $this->assertSame($identifier, $request->getImageIdentifier());
    }
}
