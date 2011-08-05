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

namespace PHPIMS;

use Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ClientTest extends \PHPUnit_Framework_TestCase {
    /**
     * Client instance
     *
     * @var PHPIMS\Client
     */
    private $client;

    /**
     * Public key
     *
     * @var string
     */
    private $publicKey;

    /**
     * Private key
     *
     * @var string
     */
    private $privateKey;

    /**
     * The server url passed to the constructor
     *
     * @var string
     */
    private $serverUrl = 'http://host';

    /**
     * Image identifier used for tests
     *
     * @var string
     */
    private $imageIdentifier;

    /**
     * Pattern used in the Mockery matchers when url is signed
     *
     * @var string
     */
    private $signedUrlPattern = '|^http://host/[a-f0-9]{32}/[a-f0-9]{32}\.png(/meta)?\?signature=(.*?)&timestamp=\d\d\d\d-\d\d-\d\dT\d\d%3A\d\dZ$|';

    /**
     * Pattern used in the Mockery matchers with regular urls
     *
     * @var string
     */
    private $urlPattern = '|^http://host/[a-f0-9]{32}/[a-f0-9]{32}\.png(/meta)?$|';

    /**
     * Set up method
     */
    public function setUp() {
        $this->publicKey = md5(microtime());
        $this->privateKey = md5(microtime());
        $this->imageIdentifier = md5(microtime()) . '.png';
        $this->driver = m::mock('PHPIMS\Client\Driver\DriverInterface');

        $this->client = new Client($this->serverUrl, $this->publicKey, $this->privateKey, $this->driver);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->client = null;
    }

    public function testAddImage() {
        $imagePath = __DIR__ . '/_files/image.png';
        $metadata = array(
            'foo' => 'bar',
            'bar' => 'foo',
            'filename' => 'image.png',
        );

        $response = m::mock('PHPIMS\Client\Response');
        $response->shouldReceive('isSuccess')->once()->andReturn(true);

        $this->driver->shouldReceive('put')->once()->with($this->signedUrlPattern, $imagePath)->andReturn($response);
        $this->driver->shouldReceive('post')->once()->with($this->signedUrlPattern, $metadata)->andReturn($response);

        $result = $this->client->addImage($imagePath, $metadata);

        $this->assertSame($result, $response);
    }

    public function testDeleteImage() {
        $response = m::mock('PHPIMS\Client\Response');
        $this->driver->shouldReceive('delete')->once()->with($this->signedUrlPattern)->andReturn($response);

        $result = $this->client->deleteImage($this->imageIdentifier);

        $this->assertSame($result, $response);
    }

    public function testEditMetaData() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );

        $response = m::mock('PHPIMS\Client\Response');
        $this->driver->shouldReceive('post')->once()->with($this->signedUrlPattern, $data)->andReturn($response);
        $result = $this->client->editMetaData($this->imageIdentifier, $data);

        $this->assertSame($result, $response);
    }

    public function testDeleteMetaData() {
        $response = m::mock('PHPIMS\Client\Response');
        $this->driver->shouldReceive('delete')->once()->with($this->signedUrlPattern)->andReturn($response);
        $result = $this->client->deleteMetaData($this->imageIdentifier);

        $this->assertSame($result, $response);
    }

    public function testGetMetaData() {
        $response = m::mock('PHPIMS\Client\Response');
        $this->driver->shouldReceive('get')->once()->with($this->urlPattern)->andReturn($response);
        $result = $this->client->getMetadata($this->imageIdentifier);

        $this->assertSame($result, $response);
    }
}
