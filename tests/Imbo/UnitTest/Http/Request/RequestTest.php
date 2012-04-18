<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest\Http\Request;

use Imbo\Http\Request\Request,
    Imbo\Image\TransformationChain;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Http\Request\Request
 */
class RequestTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Http\Request\Request::getTransformations
     */
    public function testGetTransformationsWithNoTransformationsPresent() {
        $request = new Request();
        $this->assertEquals(new TransformationChain(), $request->getTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getTransformations
     */
    public function testGetTransformations() {
        $query = array(
            't' => array(
                // Valid transformations with all options
                'border:color=fff,width=2,height=2',
                'compress:quality=90',
                'crop:x=1,y=2,width=3,height=4',
                'resize:width=100,height=100',
                'maxSize:width=100,height=100',
                'rotate:angle=45,bg=fff',
                'thumbnail:width=100,height=100,fit=outbound',
                'canvas:width=100,height=100,x=10,y=10,bg=000',

                // Transformations with no options
                'flipHorizontally',
                'flipVertically',
            ),
        );

        $request = new Request($query);
        $chain = $request->getTransformations();
        $this->assertInstanceOf('Imbo\Image\TransformationChain', $chain);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Invalid transformation: foo
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getTransformations
     */
    public function testGetTransformationsWithInvalidTransformation() {
        $query = array(
            't' => array(
                // Valid transformations
                'thumbnail:width=100,height=100,fit=outbound',
                'canvas:width=100,height=100,x=10,y=10,bg=000',

                // Invalid
                'foo:param=1',
            ),
        );

        $request = new Request($query);
        $chain = $request->getTransformations();
        $this->assertInstanceOf('Imbo\Image\TransformationChain', $chain);
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getImageIdentifier
     * @covers Imbo\Http\Request\Request::setImageIdentifier
     */
    public function testSetGetImageIdentifier() {
        $request = new Request();
        $identifier = md5(microtime());
        $this->assertNull($request->getImageIdentifier());
        $this->assertSame($request, $request->setImageIdentifier($identifier));
        $this->assertSame($identifier, $request->getImageIdentifier());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getImageExtension
     * @covers Imbo\Http\Request\Request::setImageExtension
     */
    public function testSetGetImageExtension() {
        $request = new Request();
        $extension = 'gif';
        $this->assertNull($request->getImageExtension());
        $this->assertSame($request, $request->setImageExtension($extension));
        $this->assertSame($extension, $request->getImageExtension());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getQuery
     */
    public function testGetQuery() {
        $request = new Request(array('key' => 'value'));
        $queryContainer = $request->getQuery();
        $this->assertInstanceOf('Imbo\Http\ParameterContainer', $queryContainer);
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getRequest
     */
    public function testGetRequest() {
        $request = new Request(array(), array('key' => 'value'));
        $requestContainer = $request->getRequest();
        $this->assertInstanceOf('Imbo\Http\ParameterContainer', $requestContainer);
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getServer
     */
    public function testGetServer() {
        $request = new Request(array(), array(), array('key' => 'value'));
        $container = $request->getServer();
        $this->assertInstanceOf('Imbo\Http\ServerContainer', $container);
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getHeaders
     */
    public function testGetHeaders() {
        $request = new Request(array(), array(), array('key' => 'value'));
        $container = $request->getHeaders();
        $this->assertInstanceOf('Imbo\Http\HeaderContainer', $container);
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::isUnsafe
     * @covers Imbo\Http\Request\Request::getMethod
     */
    public function testIsUnsafe() {
        $request = new Request(array(), array(), array('REQUEST_METHOD' => 'GET'));
        $this->assertFalse($request->isUnsafe());
        $request = new Request(array(), array(), array('REQUEST_METHOD' => 'HEAD'));
        $this->assertFalse($request->isUnsafe());
        $request = new Request(array(), array(), array('REQUEST_METHOD' => 'PUT'));
        $this->assertTrue($request->isUnsafe());
        $request = new Request(array(), array(), array('REQUEST_METHOD' => 'POST'));
        $this->assertTrue($request->isUnsafe());
        $request = new Request(array(), array(), array('REQUEST_METHOD' => 'DELETE'));
        $this->assertTrue($request->isUnsafe());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::setPublicKey
     * @covers Imbo\Http\Request\Request::getPublicKey
     */
    public function testSetGetPublicKey() {
        $request = new Request();
        $publicKey = 'publicKey';
        $this->assertSame($request, $request->setPublicKey($publicKey));
        $this->assertSame($publicKey, $request->getPublicKey());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::setPrivateKey
     * @covers Imbo\Http\Request\Request::getPrivateKey
     */
    public function testSetGetPrivateKey() {
        $request = new Request();
        $privateKey = '55b90a334854ac17b91f5c5690944f31';
        $this->assertSame($request, $request->setPrivateKey($privateKey));
        $this->assertSame($privateKey, $request->getPrivateKey());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getPath
     */
    public function testGetPath() {
        $request = new Request();
        $this->assertEmpty($request->getPath());

    }

    /**
     * If public/index.php is not placed directly in the document root there is logic in the
     * request class the removes a possible prefix from the path. This method will test that
     * functionality.
     *
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getPath
     */
    public function testGetPathWithPrefixUsedForImboInstallation() {
        $publicKey = md5(microtime());
        $imageIdentifier = md5(microtime());

        $server = array(
            'DOCUMENT_ROOT' => '/var/www/imbo',
            'SCRIPT_FILENAME' => '/var/www/imbo/public/index.php',
            'REDIRECT_URL' => '/public/users/' . $publicKey . '/images/' . $imageIdentifier,
        );

        $request = new Request(array(), array(), $server);

        $this->assertSame('/users/' . $publicKey . '/images/' . $imageIdentifier, $request->getPath());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getScheme
     */
    public function testGetSchemeWithHttps() {
        $request = new Request(array(), array(), array('HTTPS' => 1));
        $this->assertSame('https', $request->getScheme());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getScheme
     */
    public function testGetSchemeWithHttp() {
        $request = new Request();
        $this->assertSame('http', $request->getScheme());
    }

    public function getHosts() {
        return array(
            array('localhost', 'localhost'),
            array('localhost:81', 'localhost'),
        );
    }

    /**
     * @dataProvider getHosts()
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getHost
     */
    public function testGetHost($host, $expected) {
        $request = new Request(array(), array(), array('HTTP_HOST' => $host));
        $this->assertSame($expected, $request->getHost());
    }

    public function getUrls() {
        $publicKey = md5(microtime());

        return array(
            array(
                'localhost', 80,
                array('accessToken' => 'token'),
                '/var/www', '/var/www/index.php',
                '/users/' . $publicKey,
                'http://localhost/users/' . $publicKey . '?accessToken=token'
            ),

            array(
                'localhost', 80,
                array(
                    'accessToken' => 'token',
                    't' => array(
                        'border',
                        'thumbnail',
                        'flipVertically'
                    ),
                ),
                '/var/www', '/var/www/prefix/index.php',
                '/users/' . $publicKey,
                'http://localhost/prefix/users/' . $publicKey . '?accessToken=token&t[]=border&t[]=thumbnail&t[]=flipVertically'
            ),

            array(
                'localhost:81', 81,
                array(),
                '/var/www', '/var/www/index.php',
                '/users/' . $publicKey,
                'http://localhost:81/users/' . $publicKey
            ),

            array(
                'localhost:81', 81,
                array(),
                '/var/www', '/var/www/prefix/index.php',
                '/users/' . $publicKey,
                'http://localhost:81/prefix/users/' . $publicKey
            ),

            array(
                'localhost:80', 80,
                array(),
                '/var/www', '/var/www/index.php',
                '/users/' . $publicKey,
                'http://localhost/users/' . $publicKey
            ),

            array(
                'localhost:80', 80,
                array(),
                '/var/www', '/var/www/prefix/index.php',
                '/users/' . $publicKey,
                'http://localhost/prefix/users/' . $publicKey
            ),

            array(
                'localhost', 443,
                array(),
                '/var/www', '/var/www/index.php',
                '/users/' . $publicKey,
                'https://localhost/users/' . $publicKey,
                true
            ),

            array(
                'localhost', 443,
                array(),
                '/var/www', '/var/www/prefix/index.php',
                '/users/' . $publicKey,
                'https://localhost/prefix/users/' . $publicKey,
                true
            ),

            array(
                'localhost:444', 444,
                array(),
                '/var/www', '/var/www/index.php',
                '/users/' . $publicKey,
                'https://localhost:444/users/' . $publicKey,
                true
            ),

            array(
                'localhost:444', 444,
                array(),
                '/var/www', '/var/www/prefix/index.php',
                '/users/' . $publicKey,
                'https://localhost:444/prefix/users/' . $publicKey,
                true
            ),

            array(
                'localhost:443', 443,
                array(),
                '/var/www', '/var/www/index.php',
                '/users/' . $publicKey,
                'https://localhost/users/' . $publicKey,
                true
            ),

            array(
                'localhost:443', 443,
                array(
                    't' => array(
                        'flipHorizontally',
                        'border',
                    ),
                    'accessToken' => 'token',
                ),
                '/var/www', '/var/www/prefix/index.php',
                '/users/' . $publicKey,
                'https://localhost/prefix/users/' . $publicKey . '?t[]=flipHorizontally&t[]=border&accessToken=token',
                true
            ),
        );
    }

    /**
     * @dataProvider getUrls()
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getUrl
     */
    public function testGetUrlWithImboInDocumentRoot($host, $port, array $queryParams, $documentRoot, $scriptFilename, $redirectUrl, $expected, $ssl = false) {
        $request = new Request(
            $queryParams,
            array(),
            array(
                'HTTP_HOST' => $host,
                'DOCUMENT_ROOT' => $documentRoot,
                'SCRIPT_FILENAME' => $scriptFilename,
                'REDIRECT_URL' => $redirectUrl,
                'SERVER_PORT' => $port,
                'HTTPS' => (int) $ssl,
            )
        );

        $this->assertSame($expected, $request->getUrl());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getPort
     */
    public function testGetPort() {
        $port = 80;
        $request = new Request(array(), array(), array(
            'SERVER_PORT' => $port,
        ));
        $this->assertSame($port, $request->getPort());
    }

    public function getBaseUrlData() {
        return array(
            array('/doc/root', '/doc/root/index.php', ''),
            array('/doc/root/', '/doc/root/index.php', ''),
            array('/doc/root', '/doc/root/dir/index.php', '/dir'),
            array('/doc/root/', '/doc/root/dir/index.php', '/dir'),
        );
    }

    /**
     * @dataProvider getBaseUrlData
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getBaseUrl
     */
    public function testGetBaseUrl($documentRoot, $script, $baseUrl) {
        $request = new Request(array(), array(), array(
            'DOCUMENT_ROOT' => $documentRoot,
            'SCRIPT_FILENAME' => $script,
        ));
        $this->assertSame($baseUrl, $request->getBaseUrl());
    }

    /**
     * @covers Imbo\Http\Request\Request::getRawData
     * @covers Imbo\Http\Request\Request::setRawData
     */
    public function testSetGetRawImageData() {
        $image = file_get_contents(FIXTURES_DIR . '/image.png');
        $request = new Request();
        $this->assertSame($request, $request->setRawData($image));
        $this->assertSame($image, $request->getRawData());
    }

    /**
     * @covers Imbo\Http\Request\Request::getRealImageIdentifier
     */
    public function testGetRealImageIdentifier() {
        $request = new Request();
        $this->assertNull($request->getRealImageIdentifier());

        $image = file_get_contents(FIXTURES_DIR . '/image.png');
        $this->assertSame($request, $request->setRawData($image));
        $this->assertSame('929db9c5fc3099f7576f5655207eba47', $request->getRealImageIdentifier());
    }
}
