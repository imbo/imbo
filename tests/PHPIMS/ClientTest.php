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
     * Set up method
     */
    public function setUp() {
        $this->client = new PHPIMS_Client();
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
        $driver = $this->getMockForAbstractClass('PHPIMS_Client_Driver_Abstract');
        $this->client->setDriver($driver);
        $this->assertSame($driver, $this->client->getDriver());
    }

    public function testConstructorParams() {
        $driver = $this->getMockForAbstractClass('PHPIMS_Client_Driver_Abstract');
        $client = new PHPIMS_Client($driver);
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
        $url      = 'http://host';
        $image    = __DIR__ . '/_files/image.png';
        $metadata = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $md5 = md5_file($image);

        $response = $this->getMock('PHPIMS_Client_Response');

        $driver = $this->getMockForAbstractClass('PHPIMS_Client_Driver_Abstract');
        $driver->expects($this->once())->method('addImage')->with($image, $url . '/' . $md5 . '.png', $metadata)->will($this->returnValue($response));

        $result = $this->client->setDriver($driver)
                               ->setServerUrl($url)
                               ->addImage($image, $metadata);

        $this->assertSame($result, $response);
    }

    public function testDeleteImage() {
        $url  = 'http://host';
        $hash = 'Some hash';

        $response = $this->getMock('PHPIMS_Client_Response');

        $driver = $this->getMockForAbstractClass('PHPIMS_Client_Driver_Abstract');
        $driver->expects($this->once())->method('delete')->with($url . '/' . $hash)->will($this->returnValue($response));

        $result = $this->client->setDriver($driver)
                               ->setServerUrl($url)
                               ->deleteImage($hash);

        $this->assertSame($result, $response);
    }

    public function testEditMetaData() {
        $url  = 'http://host';
        $hash = 'Some hash';
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );

        $response = $this->getMock('PHPIMS_Client_Response');

        $driver = $this->getMockForAbstractClass('PHPIMS_Client_Driver_Abstract');
        $driver->expects($this->once())->method('post')->with($url . '/' . $hash . '/meta', $data)->will($this->returnValue($response));

        $result = $this->client->setDriver($driver)
                               ->setServerUrl($url)
                               ->editMetaData($hash, $data);

        $this->assertSame($result, $response);
    }

    public function testDeleteMetaData() {
        $url  = 'http://host';
        $hash = 'Some hash';

        $response = $this->getMock('PHPIMS_Client_Response');

        $driver = $this->getMockForAbstractClass('PHPIMS_Client_Driver_Abstract');
        $driver->expects($this->once())->method('delete')->with($url . '/' . $hash . '/meta')->will($this->returnValue($response));

        $result = $this->client->setDriver($driver)
                               ->setServerUrl($url)
                               ->deleteMetaData($hash);

        $this->assertSame($result, $response);
    }

    public function testGetMetaData() {
        $url  = 'http://host';
        $hash = 'Some hash';

        $response = $this->getMock('PHPIMS_Client_Response');

        $driver = $this->getMockForAbstractClass('PHPIMS_Client_Driver_Abstract');
        $driver->expects($this->once())->method('get')->with($url . '/' . $hash . '/meta')->will($this->returnValue($response));

        $result = $this->client->setDriver($driver)
                               ->setServerUrl($url)
                               ->getMetadata($hash);

        $this->assertSame($result, $response);
    }
}