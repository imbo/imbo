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

namespace PHPIMS\Resource\Plugin;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class AuthTest extends \PHPUnit_Framework_TestCase {
    private $plugin;
    private $request;
    private $response;
    private $database;
    private $storage;

    public function setUp() {
        $this->plugin   = new Auth();
        $this->request  = $this->getMock('PHPIMS\Request\RequestInterface');
        $this->response = $this->getMock('PHPIMS\Response\ResponseInterface');
        $this->database = $this->getMock('PHPIMS\Database\DatabaseInterface');
        $this->storage  = $this->getMock('PHPIMS\Storage\StorageInterface');
    }

    public function tearDown() {
        $this->plugin = null;
    }

    /**
     * @expectedException PHPIMS\Resource\Plugin\Exception
     * @expectedExceptionMessage Missing required parameter
     * @expectedExceptionCode 400
     */
    public function testExecWithMissingRequestParams() {
        $this->request->expects($this->at(0))->method('has')->with('signature')->will($this->returnValue(true));
        $this->request->expects($this->at(1))->method('has')->with('timestamp')->will($this->returnValue(false));

        $this->plugin->exec($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Resource\Plugin\Exception
     * @expectedExceptionMessage Invalid timestamp format
     * @expectedExceptionCode 400
     */
    public function testExecWithInvalidTimestampFormat() {
        $this->request->expects($this->at(0))->method('has')->with('signature')->will($this->returnValue(true));
        $this->request->expects($this->at(1))->method('has')->with('timestamp')->will($this->returnValue(true));

        $this->request->expects($this->once())->method('getTimestamp')->will($this->returnValue(time()));

        $this->plugin->exec($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Resource\Plugin\Exception
     * @expectedExceptionMessage Timestamp expired
     * @expectedExceptionCode 401
     */
    public function testExecWithTimestampExpired() {
        $this->request->expects($this->at(0))->method('has')->with('signature')->will($this->returnValue(true));
        $this->request->expects($this->at(1))->method('has')->with('timestamp')->will($this->returnValue(true));

        $this->request->expects($this->once())->method('getTimestamp')->will($this->returnValue('2010-01-01T00:00Z'));

        $this->plugin->exec($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Resource\Plugin\Exception
     * @expectedExceptionMessage Signature mismatch
     * @expectedExceptionCode 401
     */
    public function testExecWithWrongSignature() {
        $this->request->expects($this->at(0))->method('has')->with('signature')->will($this->returnValue(true));
        $this->request->expects($this->at(1))->method('has')->with('timestamp')->will($this->returnValue(true));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->expects($this->once())->method('getResource')->will($this->returnValue('image'));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue(md5(microtime())));
        $this->request->expects($this->once())->method('getPrivateKey')->will($this->returnValue(md5(microtime())));
        $this->request->expects($this->once())->method('getSignature')->will($this->returnValue(md5(microtime())));

        $now = gmdate('Y-m-d\TH:i\Z');

        $this->request->expects($this->exactly(2))->method('getTimestamp')->will($this->returnValue($now));

        $this->plugin->exec($this->request, $this->response, $this->database, $this->storage);
    }

    public function testSuccessfulExec() {
        $publicKey = md5(microtime());
        $privateKey = md5(microtime());
        $now = gmdate('Y-m-d\TH:i\Z');
        $method = 'GET';
        $resource = 'Image';

        // Compute signature
        $data = $method . $resource . $publicKey . $now;

        // base64 encode the signature
        $actualSignature = base64_encode(hash_hmac('sha256', $data, $privateKey, true));

        $this->request->expects($this->at(0))->method('has')->with('signature')->will($this->returnValue(true));
        $this->request->expects($this->at(1))->method('has')->with('timestamp')->will($this->returnValue(true));

        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue($method));
        $this->request->expects($this->once())->method('getResource')->will($this->returnValue($resource));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getPrivateKey')->will($this->returnValue($privateKey));
        $this->request->expects($this->once())->method('getSignature')->will($this->returnValue($actualSignature));

        $this->request->expects($this->exactly(2))->method('getTimestamp')->will($this->returnValue($now));

        $this->plugin->exec($this->request, $this->response, $this->database, $this->storage);
    }
}
