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

use \Mockery as m;

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
        $this->driver = m::mock('PHPIMS\\Client\\DriverInterface');

        $this->client = new Client($this->serverUrl, $this->publicKey, $this->privateKey, $this->driver);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->client = null;
    }

    /**
     * @expectedException PHPIMS\Client\Exception
     * @expectedExceptionMessage File does not exist: foobar
     */
    public function testGenerateImageIdentifierForFileThatDoesNotExist() {
        $this->client->getImageIdentifier('foobar');
    }

    public function testGenerateImageIdentifier() {
        $hash = $this->client->getImageIdentifier(__DIR__ . '/_files/image.png');
        $this->assertSame('929db9c5fc3099f7576f5655207eba47.png', $hash);
    }

    public function testAddImage() {
        $image    = __DIR__ . '/_files/image.png';
        $metadata = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $md5 = md5_file($image);

        $response = m::mock('PHPIMS\\Client\\Response');
        $this->driver->shouldReceive('addImage')->once()->with($image, $this->signedUrlPattern, $metadata)->andReturn($response);
        $result = $this->client->addImage($image, $metadata);

        $this->assertSame($result, $response);
    }

    public function testDeleteImage() {
        $response = m::mock('PHPIMS\\Client\\Response');
        $this->driver->shouldReceive('delete')->once()->with($this->signedUrlPattern)->andReturn($response);

        $result = $this->client->deleteImage($this->imageIdentifier);

        $this->assertSame($result, $response);
    }

    public function testEditMetaData() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );

        $response = m::mock('PHPIMS\\Client\\Response');
        $this->driver->shouldReceive('post')->once()->with($this->signedUrlPattern, $data)->andReturn($response);
        $result = $this->client->editMetaData($this->imageIdentifier, $data);

        $this->assertSame($result, $response);
    }

    public function testDeleteMetaData() {
        $response = m::mock('PHPIMS\\Client\\Response');
        $this->driver->shouldReceive('delete')->once()->with($this->signedUrlPattern)->andReturn($response);
        $result = $this->client->deleteMetaData($this->imageIdentifier);

        $this->assertSame($result, $response);
    }

    public function testGetMetaData() {
        $response = m::mock('PHPIMS\\Client\\Response');
        $this->driver->shouldReceive('get')->once()->with($this->urlPattern)->andReturn($response);
        $result = $this->client->getMetadata($this->imageIdentifier);

        $this->assertSame($result, $response);
    }

    public function testGetImageUrl() {
        $url = $this->client->getImageUrl($this->imageIdentifier);
        $this->assertInstanceOf('PHPIMS\\Client\\ImageUrl', $url);
        $this->assertSame($this->serverUrl . '/' . $this->publicKey . '/' . $this->imageIdentifier, (string) $url);
    }

    public function testGetImageUrlWithTransformations() {
        $baseUrl = $this->serverUrl . '/' . $this->publicKey . '/' . $this->imageIdentifier;
        $completeUrl = $baseUrl;
        $chain = m::mock('PHPIMS\\Image\\TransformationChain');
        $chain->shouldReceive('applyToImageUrl')->once()->with(m::type('PHPIMS\\Client\\ImageUrl'));

        $url = $this->client->getImageUrl($this->imageIdentifier, $chain);
        $this->assertInstanceOf('PHPIMS\\Client\\ImageUrl', $url);
        $this->assertSame($completeUrl, (string) $url);
    }
}
