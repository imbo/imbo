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

namespace PHPIMS\Operation\Plugin;

use \Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class AuthTest extends \PHPUnit_Framework_TestCase {
    /**
     * Plugin instance
     *
     * @var PHPIMS\Operation\Plugin\Auth
     */
    private $plugin;

    public function setUp() {
        $this->plugin = new Auth();
    }

    public function tearDown() {
        $this->plugin = null;
    }

    /**
     * @expectedException PHPIMS\Operation\Plugin\Exception
     * @expectedExceptionCode 400
     */
    public function testExecWithMissingParameters() {
        $operation = m::mock('PHPIMS\\Operation');
        $this->plugin->exec($operation);
    }

    /**
     * @expectedException PHPIMS\Operation\Plugin\Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Invalid timestamp format:
     */
    public function testExecWithInvalidTimestampFormat() {
        $_GET['signature'] = 'some signature';
        $_GET['timestamp'] = 123123123;

        $operation = m::mock('PHPIMS\\Operation');

        $this->plugin->exec($operation);
    }

    /**
     * @expectedException PHPIMS\Operation\Plugin\Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Timestamp expired
     */
    public function testExecWithTimestampTooFarInTheFuture() {
        $_GET['signature'] = 'some signature';
        $_GET['timestamp'] = gmdate('Y-m-d\TH:i\Z', time() + 300);

        $operation = m::mock('PHPIMS\\Operation');

        $this->plugin->exec($operation);
    }

    /**
     * @expectedException PHPIMS\Operation\Plugin\Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Timestamp expired
     */
    public function testExecWithTimestampTooOld() {
        $_GET['signature'] = 'some signature';
        $_GET['timestamp'] = gmdate('Y-m-d\TH:i\Z', time() - 300);

        $operation = m::mock('PHPIMS\\Operation');

        $this->plugin->exec($operation);
    }

    public function testExecWithCorrectSignature() {
        $publicKey = md5(microtime());
        $privateKey = md5(microtime());

        $this->signRequest($publicKey, $privateKey);
    }

    /**
     * @expectedException PHPIMS\Operation\Plugin\Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Signature mismatch
     */
    public function testExecWithIncorrectSignature() {
        $publicKey = md5(microtime());
        $privateKey = md5(microtime());

        $this->signRequest($publicKey, $privateKey, 'wrong signature');
    }

    protected function signRequest($publicKey, $privateKey, $signature = null) {
        // Emulate a call to the deleteMetadata operation
        $method = 'DELETE';
        $resource = md5(microtime()) . '.png/meta';
        $timestamp = gmdate('Y-m-d\TH:i\Z');

        // The data used to create the hash
        $data = $method . $resource . $publicKey . $timestamp;

        $operation = m::mock('PHPIMS\\Operation');
        $operation->shouldReceive('getMethod')->once()->andReturn($method);
        $operation->shouldReceive('getResource')->once()->andReturn($resource);
        $operation->shouldReceive('getPublicKey')->once()->andReturn($publicKey);
        $operation->shouldReceive('getPrivateKey')->once()->andReturn($privateKey);

        if ($signature === null) {
            // No signature given. Create the correct signature
            $signature = base64_encode(hash_hmac('sha256', $data, $privateKey, true));
        }

        $_GET['signature'] = $signature;
        $_GET['timestamp'] = $timestamp;

        $this->plugin->exec($operation);
    }
}
