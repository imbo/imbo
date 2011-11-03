<?php
/**
 * Imbo
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
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Http\Request;

use Imbo\Image\TransformationChain;

/**
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class RequestTest extends \PHPUnit_Framework_TestCase {
    public function testGetTransformationsWithNoTransformationsPresent() {
        $request = new Request();
        $this->assertEquals(new TransformationChain(), $request->getTransformations());
    }

    public function testGetTransformations() {
        $query = array(
            't' => array(
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
            ),
        );

        $request = new Request($query);
        $chain = $request->getTransformations();
        $this->assertInstanceOf('Imbo\Image\TransformationChain', $chain);
    }

    public function testSetGetImageIdentifier() {
        $request = new Request();
        $identifier = md5(microtime());
        $request->setImageIdentifier($identifier);
        $this->assertSame($identifier, $request->getImageIdentifier());
    }

    public function testSetGetResource() {
        $resource = 'images';
        $request = new Request();
        $this->assertSame($request, $request->setResource($resource));
        $this->assertSame($resource, $request->getResource());
    }

    public function testGetQuery() {
        $request = new Request(array('key' => 'value'));
        $queryContainer = $request->getQuery();
        $this->assertInstanceOf('Imbo\Http\ParameterContainer', $queryContainer);
    }

    public function testGetRequest() {
        $request = new Request(array(), array('key' => 'value'));
        $requestContainer = $request->getRequest();
        $this->assertInstanceOf('Imbo\Http\ParameterContainer', $requestContainer);
    }

    public function testGetServer() {
        $request = new Request(array(), array(), array('key' => 'value'));
        $container = $request->getServer();
        $this->assertInstanceOf('Imbo\Http\ServerContainer', $container);
    }

    public function testGetHeaders() {
        $request = new Request(array(), array(), array('key' => 'value'));
        $container = $request->getHeaders();
        $this->assertInstanceOf('Imbo\Http\HeaderContainer', $container);
    }

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

    public function testSetGetPublicKey() {
        $request = new Request();
        $publicKey = md5(microtime());
        $this->assertSame($request, $request->setPublicKey($publicKey));
        $this->assertSame($publicKey, $request->getPublicKey());
    }

    public function testGetPath() {
        $request = new Request();
        $this->assertEmpty($request->getPath());

    }

    /**
     * If public/index.php is not placed directly in the document root there is logic in the
     * request class the removes a possible prefix from the path. This method will test that
     * functionality.
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
}
