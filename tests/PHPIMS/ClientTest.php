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

use \Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_ClientTest extends PHPUnit_Framework_TestCase {
    /**
     * Client instance
     *
     * @var PHPIMS_Client
     */
    protected $client = null;

    /**
     * Public key
     *
     * @var string
     */
    protected $publicKey = null;

    /**
     * Private key
     *
     * @var string
     */
    protected $privateKey = null;

    /**
     * The server url passed to the constructor
     *
     * @var string
     */
    protected $serverUrl = 'http://host';

    /**
     * Hash used for tests
     *
     * @var string
     */
    protected $hash = null;

    /**
     * Pattern used in the Mockery matchers when url is signed
     *
     * @var string
     */
    protected $signedUrlPattern = '|^http://host/[a-z0-9]{32}\.png(/meta)?\?signature=(.*?)&publicKey=[a-z0-9]{32}&timestamp=\d\d\d\d-\d\d-\d\dT\d\d%3A\d\dZ$|';

    /**
     * Pattern used in the Mockery matchers with regular urls
     *
     * @var string
     */
    protected $urlPattern = '|^http://host/[a-z0-9]{32}\.png(/meta)?$|';

    /**
     * Set up method
     */
    public function setUp() {
        $this->publicKey = md5(microtime());
        $this->privateKey = md5($this->publicKey);
        $this->hash = md5(microtime()) . '.png';

        $this->client = new PHPIMS_Client($this->serverUrl, $this->publicKey, $this->privateKey);

        $this->driver = m::mock('PHPIMS_Client_Driver');
        $this->driver->shouldReceive('setClient')->once()->with($this->client)->andReturn($this->driver);

        $this->client->setDriver($this->driver);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->client = null;
    }

    public function testSetGetServerUrl() {
        $url = 'http://localhost';
        $this->client->setServerUrl($url);
        $this->assertSame($url, $this->client->getServerUrl());
    }

    public function testSetGetTimeout() {
        $timeout = 123;
        $this->client->setTimeout($timeout);
        $this->assertSame($timeout, $this->client->getTimeout());
    }

    public function testSetGetConnectTimeout() {
        $timeout = 123;
        $this->client->setConnectTimeout($timeout);
        $this->assertSame($timeout, $this->client->getConnectTimeout());
    }

    public function testSetGetDriver() {
        $driver = m::mock('PHPIMS_Client_Driver');
        $driver->shouldReceive('setClient')->with($this->client)->andReturn($driver);
        $this->client->setDriver($driver);
        $this->assertSame($driver, $this->client->getDriver());
    }

    public function testSetGetPublicKey() {
        $key = md5(microtime());
        $this->client->setPublicKey($key);
        $this->assertSame($key, $this->client->getPublicKey());
    }

    public function testSetGetPrivateKey() {
        $key = md5(microtime());
        $this->client->setPrivateKey($key);
        $this->assertSame($key, $this->client->getPrivateKey());
    }

    public function testConstructorParams() {
        $driver = m::mock('PHPIMS_Client_Driver');
        $driver->shouldReceive('setClient')->with(m::type('PHPIMS_Client'))->andReturn($driver);
        $client = new PHPIMS_Client($this->serverUrl, $this->publicKey, $this->privateKey, $driver);

        $this->assertSame($this->serverUrl, $client->getServerUrl());
        $this->assertSame($this->publicKey, $client->getPublicKey());
        $this->assertSame($this->privateKey, $client->getPrivateKey());
        $this->assertSame($driver, $client->getDriver());
    }

    /**
     * @expectedException PHPIMS_Client_Exception
     * @expectedExceptionMessage File does not exist: foobar
     */
    public function testAddImageThatDoesNotExist() {
        $this->client->addImage('foobar');
    }

    public function testAddImage() {
        $image    = __DIR__ . '/_files/image.png';
        $metadata = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $md5 = md5_file($image);

        $response = m::mock('PHPIMS_Client_Response');
        $this->driver->shouldReceive('addImage')->once()->with($image, $this->signedUrlPattern, $metadata)->andReturn($response);
        $result = $this->client->addImage($image, $metadata);

        $this->assertSame($result, $response);
    }

    public function testDeleteImage() {
        $response = m::mock('PHPIMS_Client_Response');
        $this->driver->shouldReceive('delete')->once()->with($this->signedUrlPattern)->andReturn($response);

        $result = $this->client->deleteImage($this->hash);

        $this->assertSame($result, $response);
    }

    public function testEditMetaData() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );

        $response = m::mock('PHPIMS_Client_Response');
        $this->driver->shouldReceive('post')->once()->with($this->signedUrlPattern, $data)->andReturn($response);
        $result = $this->client->editMetaData($this->hash, $data);

        $this->assertSame($result, $response);
    }

    public function testDeleteMetaData() {
        $response = m::mock('PHPIMS_Client_Response');
        $this->driver->shouldReceive('delete')->once()->with($this->signedUrlPattern)->andReturn($response);
        $result = $this->client->deleteMetaData($this->hash);

        $this->assertSame($result, $response);
    }

    public function testGetMetaData() {
        $response = m::mock('PHPIMS_Client_Response');
        $this->driver->shouldReceive('get')->once()->with($this->urlPattern)->andReturn($response);
        $result = $this->client->getMetadata($this->hash);

        $this->assertSame($result, $response);
    }
}