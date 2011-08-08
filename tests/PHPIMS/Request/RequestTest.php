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

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class RequestTest extends \PHPUnit_Framework_TestCase {
    public function testConstructorWithValidResource() {
        $query = '/26a46b14849beb78e36883aabee0b5e0/b8533858299b04af3afc9a3713e69358.jpeg/meta';
        $request = new Request(RequestInterface::GET, $query);

        $this->assertSame('26a46b14849beb78e36883aabee0b5e0', $request->getPublicKey());
        $this->assertSame('b8533858299b04af3afc9a3713e69358.jpeg', $request->getImageIdentifier());
        $this->assertSame('b8533858299b04af3afc9a3713e69358.jpeg/meta', $request->getResource());
        $this->assertSame(RequestInterface::GET, $request->getMethod());
    }

    /**
     * @expectedException PHPIMS\Request\Exception
     * @expectedExceptionMessage Unknown resource: foobar
     */
    public function testConstructorWithInvalidResource() {
        $query = 'foobar';
        $request = new Request(RequestInterface::GET, $query);
    }

    /**
     * @expectedException PHPIMS\Request\Exception
     * @expectedExceptionMessage Unsupported HTTP method: TRACE
     */
    public function testConstructorWithUnsupportedMethod() {
        $query = '/26a46b14849beb78e36883aabee0b5e0/b8533858299b04af3afc9a3713e69358.jpeg';
        $request = new Request('TRACE', $query);
    }

    public function testGetTimestamp() {
        $timestamp = '2011-06-16T06:15Z';
        $_GET['timestamp'] = $timestamp;

        $query = '/26a46b14849beb78e36883aabee0b5e0/b8533858299b04af3afc9a3713e69358.jpeg';
        $request = new Request(RequestInterface::GET, $query);
        $this->assertSame($timestamp, $request->getTimestamp());
    }

    public function testGetMetadata() {
        $metadata = array('some' => 'data');
        $_POST['metadata'] = json_encode($metadata);
        $query = '/26a46b14849beb78e36883aabee0b5e0/b8533858299b04af3afc9a3713e69358.jpeg/meta';
        $request = new Request(RequestInterface::POST, $query);

        $this->assertSame($metadata, $request->getMetadata());
    }

    public function testGetSignature() {
        $signature = 'somesignature';
        $_GET['signature'] = $signature;

        $query = '/26a46b14849beb78e36883aabee0b5e0/b8533858299b04af3afc9a3713e69358.jpeg';
        $request = new Request(RequestInterface::DELETE, $query);
        $this->assertSame($signature, $request->getSignature());
    }

    public function testGetTransformations() {
        $_GET['t'] = array(
            'thumbnail', 'flipHorizontally', 'flipVertically', 'crop:x=0,y=10,width=100,height=200',
            'border:color=fff,width=3,height=2', 'compress:quality=50', 'resize:width=100,height=200',
            'rotate:angle=45,bg=fff',
        );
        $query = '/26a46b14849beb78e36883aabee0b5e0/b8533858299b04af3afc9a3713e69358.jpeg';
        $request = new Request(RequestInterface::GET, $query);
        $chain = $request->getTransformations();

        $this->assertInstanceOf('PHPIMS\Image\TransformationChain', $chain);
        $this->assertSame(8, count($chain));
    }

    public function testGetTransformationsWithNoneSpecified() {
        $query = '/26a46b14849beb78e36883aabee0b5e0/b8533858299b04af3afc9a3713e69358.jpeg';
        $request = new Request(RequestInterface::GET, $query);
        $chain = $request->getTransformations();

        $this->assertInstanceOf('PHPIMS\Image\TransformationChain', $chain);
        $this->assertSame(0, count($chain));
    }

    public function testGetMetadataWithNoneSpecified() {
        $query = '/26a46b14849beb78e36883aabee0b5e0/b8533858299b04af3afc9a3713e69358.jpeg';
        $request = new Request(RequestInterface::POST, $query);

        $this->assertNull($request->getMetadata());
    }
}
