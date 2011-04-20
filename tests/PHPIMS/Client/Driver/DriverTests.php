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

namespace PHPIMS\Client\Driver;

use PHPIMS\Client;
use PHPIMS\Client\Response;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
abstract class DriverTests extends \PHPUnit_Framework_TestCase {
    /**
     * The driver instance
     *
     * @var PHPIMS\Client\Driver
     */
    protected $driver = null;

    /**
     * URL to the script that the tests should send requests to
     *
     * @var string
     */
    protected $testUrl = null;

    /**
     * Return an instance of the driver we are testing
     *
     * @return PHPIMS\Client\Driver
     */
    abstract protected function getNewDriver();

    /**
     * Setup the driver
     */
    public function setUp() {
        if (!PHPIMS_ENABLE_CLIENT_TESTS) {
            $this->markTestSkipped('PHPIMS_ENABLE_CLIENT_TESTS must be set to true to run these tests');
        }

        $this->driver  = $this->getNewDriver();
        $this->testUrl = PHPIMS_CLIENT_TESTS_URL;
    }

    /**
     * Tear down the driver
     */
    public function tearDown() {
        $this->driver = null;
    }

    public function testPost() {
        $metadata = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $response = $this->driver->post($this->testUrl, $metadata);
        $this->assertInstanceOf('PHPIMS\\Client\\Response', $response);

        $result = unserialize($response->getBody());
        $this->assertSame('POST', $result['method']);
        $this->assertSame($metadata, json_decode($result['data']['metadata'], true));
    }

    public function testGet() {
        $url = $this->testUrl . '?foo=bar&bar=foo';
        $response = $this->driver->get($url);
        $this->assertInstanceOf('PHPIMS\\Client\\Response', $response);
        $result = unserialize($response->getBody());
        $this->assertSame('GET', $result['method']);
        $this->assertSame(array('foo' => 'bar', 'bar' => 'foo'), $result['data']);
    }

    public function testHead() {
        $response = $this->driver->head($this->testUrl);
        $this->assertInstanceOf('PHPIMS\\Client\\Response', $response);
        $this->assertEmpty($response->getBody());
    }

    public function testDelete() {
        $response = $this->driver->delete($this->testUrl);
        $this->assertInstanceOf('PHPIMS\\Client\\Response', $response);
        $result = unserialize($response->getBody());
        $this->assertSame('DELETE', $result['method']);
    }

    public function testAddImage() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $response = $this->driver->addImage(__FILE__, $this->testUrl, $data);
        $this->assertInstanceOf('PHPIMS\\Client\\Response', $response);
        $result = unserialize($response->getBody());
        $this->assertSame('POST', $result['method']);
        $this->assertArrayHasKey('files', $result);
    }

    /**
     * @expectedException PHPIMS\Client\Driver\Exception
     */
    public function testReadTimeout() {
        $url = $this->testUrl . '?sleep=3';
        $this->driver->get($url);
    }
}