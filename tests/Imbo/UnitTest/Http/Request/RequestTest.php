<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http\Request;

use Imbo\Http\Request\Request;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\Http\Request\Request
 */
class RequestTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Http\Request\Request::getTransformations
     */
    public function testGetTransformationsWithNoTransformationsPresent() {
        $request = new Request();
        $this->assertEquals(array(), $request->getTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getTransformations
     */
    public function testGetTransformationsWithCorrectOrder() {
        $query = array(
            't' => array(
                'flipHorizontally',
                'flipVertically',
            ),
        );

        $request = new Request($query);
        $transformations = $request->getTransformations();
        $this->assertEquals('flipHorizontally', $transformations[0]['name']);
        $this->assertEquals('flipVertically',   $transformations[1]['name']);
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

                // Transformations with no options
                'flipHorizontally',
                'flipVertically',

                // The same transformation can be applied multiple times
                'resize:width=50,height=75',
            ),
        );

        $request = new Request($query);
        $transformations = $request->getTransformations();
        $this->assertInternalType('array', $transformations);
        $this->assertSame(7, count($transformations));

        $this->assertEquals(array('color' => 'fff', 'width' => 2, 'height' => 2), $transformations[0]['params']);
        $this->assertEquals(array('quality' => '90'), $transformations[1]['params']);
        $this->assertEquals(array('x' => 1, 'y' => 2, 'width' => 3, 'height' => 4), $transformations[2]['params']);
        $this->assertEquals(array('width' => 100, 'height' => 100), $transformations[3]['params']);
        $this->assertEquals(array(), $transformations[4]['params']);
        $this->assertEquals(array(), $transformations[5]['params']);
        $this->assertEquals(array('width' => 50, 'height' => 75), $transformations[6]['params']);
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
     * @covers Imbo\Http\Request\Request::getExtension
     * @covers Imbo\Http\Request\Request::setExtension
     */
    public function testSetGetExtension() {
        $request = new Request();
        $extension = 'gif';
        $this->assertNull($request->getExtension());
        $this->assertSame($request, $request->setExtension($extension));
        $this->assertSame($extension, $request->getExtension());
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
     * @covers Imbo\Http\Request\Request::__construct
     * @covers Imbo\Http\Request\Request::getPath
     */
    public function testGetPathWithQueryParameters() {
        $publicKey = md5(microtime());
        $imageIdentifier = md5(microtime());
        $path = '/users/' . $publicKey . '/images/' . $imageIdentifier;

        $server = array(
            'DOCUMENT_ROOT' => '/var/www/imbo',
            'SCRIPT_FILENAME' => '/var/www/imbo/public/index.php',
            'REQUEST_URI' => '/public' . $path . '?foo=bar',
        );

        $request = new Request(array(), array(), $server);
        $this->assertSame($path, $request->getPath());
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
            'REQUEST_URI' => '/public/users/' . $publicKey . '/images/' . $imageIdentifier,
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
                'REQUEST_URI' => $redirectUrl,
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

    /**
     * Fetch different base URLs
     *
     * @return array[]
     */
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
     * @dataProvider splitAcceptHeaderData
     */
    public function testSplitAcceptHeader($header, $expected) {
        $request = new Request();
        $this->assertEquals($expected, $request->splitAcceptHeader($header));
    }

    /**
     * Test values are from RFC2616, section 14.1
     */
    public function splitAcceptHeaderData() {
        return array(
            array(null, array()),

            array('audio/*; q=0.2, audio/basic', array(
                'audio/basic' => 1,
                'audio/*' => 0.2,
            )),

            array('text/plain; q=0.5, text/html, text/x-dvi; q=0.8, text/x-c', array(
                'text/html' => 1,
                'text/x-c' => 1,
                'text/x-dvi' => 0.8,
                'text/plain' => 0.5,
            )),

            array('text/*, text/html, text/html;level=1, */*', array(
                'text/html;level=1' => 1,
                'text/html' => 1,
                'text/*' => 1,
                '*/*' => 1,
            )),

            array('text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5', array(
                'text/html;level=1' => 1,
                'text/html' => 0.7,
                '*/*' => 0.5,
                'text/html;level=2' => 0.4,
                'text/*' => 0.3,
            )),
        );
    }

    /**
     * @covers Imbo\Http\Request\Request::getResource
     * @covers Imbo\Http\Request\Request::setResource
     */
    public function testSetGetResource() {
        $request = new Request();
        $this->assertSame($request, $request->setResource('metadata'));
        $this->assertSame('metadata', $request->getResource());
    }

    /**
     * @covers Imbo\Http\Request\Request::hasTransformations
     */
    public function testHasTransformationsWithExtension() {
        $request = new Request();
        $request->setExtension('png');
        $this->assertTrue($request->hasTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::hasTransformations
     */
    public function testHasTransformationsWithTransformationsInQuery() {
        $request = new Request(array('t' => array('flipHorizontally')));
        $this->assertTrue($request->hasTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::hasTransformations
     */
    public function testHasTransformationsWithNoTransformations() {
        $request = new Request();
        $this->assertFalse($request->hasTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::getImage
     * @covers Imbo\Http\Request\Request::setImage
     */
    public function testCanSetAndGetAnImage() {
        $request = new Request();
        $image = $this->getMock('Imbo\Image\Image');
        $this->assertSame($request, $request->setImage($image));
        $this->assertSame($image, $request->getImage());
    }

    /**
     * Get Accept headers
     *
     * @return array[]
     */
    public function getAcceptHeader() {
        return array(
            array('image/jpeg', array('image/jpeg' => 1.0)),
            array('', array()),
        );
    }

    /**
     * @dataProvider getAcceptHeader
     * @covers Imbo\Http\Request\Request::getAcceptableContentTypes
     * @covers Imbo\Http\Request\Request::splitAcceptHeader
     */
    public function testCanFetchAcceptableContentTypesBasedOnTheAcceptHeader($accept, $expected) {
        $request = new Request(array(), array(), array('HTTP_ACCEPT' => $accept));
        $this->assertSame($expected, $request->getAcceptableContentTypes());
    }
}
