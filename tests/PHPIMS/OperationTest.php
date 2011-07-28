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
class OperationTest extends \PHPUnit_Framework_TestCase {
    /**
     * Operation instance
     *
     * @var PHPIMS\Operation
     */
    private $operation;

    /**
     * Set up method
     */
    public function setUp() {
        $this->operation = $this->getMockBuilder('PHPIMS\\Operation')->setMethods(array('getOperationName', 'exec'))
                                ->disableOriginalConstructor()
                                ->getMock();

        // Make the operation return "addImage" as if it was the PHPIMS\Operation\AddImage
        // operation class
        $this->operation->expects($this->any())
                        ->method('getOperationName')
                        ->will($this->returnValue('addImage'));

        $this->operation->expects($this->any())
                        ->method('exec');
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->operation = null;
    }

    public function testSetGetImageIdentifier() {
        $imageIdentifier = md5(time()) . '.png';
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setImageIdentifier($imageIdentifier));
        $this->assertSame($imageIdentifier, $this->operation->getImageIdentifier());
    }

    public function testSetGetDatabase() {
        $driver = m::mock('PHPIMS\\Database\\DriverInterface');
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setDatabase($driver));
        $this->assertSame($driver, $this->operation->getDatabase());
    }

    public function testSetGetStorage() {
        $driver = m::mock('PHPIMS\\Storage\\DriverInterface');
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setStorage($driver));
        $this->assertSame($driver, $this->operation->getStorage());
    }

    public function testSetGetImage() {
        $image = $this->getMock('PHPIMS\\Image');
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setImage($image));
        $this->assertSame($image, $this->operation->getImage());
    }

    public function testSetGetResponse() {
        $response = $this->getMock('PHPIMS\\Server\\Response');
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setResponse($response));
        $this->assertSame($response, $this->operation->getResponse());
    }

    public function testSetGetMethod() {
        $method = 'DELETE';
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setMethod($method));
        $this->assertSame($method, $this->operation->getMethod());
    }

    public function testSetGetConfig() {
        $config = array(
            'foo' => 'bar',
            'bar' => 'foo',

            'sub' => array(
                'foo' => 'bar',
                'bar' => 'foo',
            ),
        );

        $this->operation->setConfig($config);
        $this->assertSame($config, $this->operation->getConfig());
        $this->assertSame($config['sub'], $this->operation->getConfig('sub'));
    }

    public function testFactory() {
        $operations = array(
            'POST'   => 'PHPIMS\\Operation\\AddImage',
            'POST'   => 'PHPIMS\\Operation\\EditImageMetadata',
            'DELETE' => 'PHPIMS\\Operation\\DeleteImage',
            'DELETE' => 'PHPIMS\\Operation\\DeleteImageMetadata',
            'GET'    => 'PHPIMS\\Operation\\GetImage',
            'GET'    => 'PHPIMS\\Operation\\GetImageMetadata',
        );
        $database = m::mock('PHPIMS\\Database\\DriverInterface');
        $storage = m::mock('PHPIMS\\Storage\\DriverInterface');

        foreach ($operations as $method => $className) {
            $this->assertInstanceOf($className, Operation::factory($className, $database, $storage));
        }
    }

    /**
     * @expectedException PHPIMS\Operation\Exception
     */
    public function testFactoryWithUnSupportedOperation() {
        $database = m::mock('PHPIMS\\Database\\DriverInterface');
        $storage = m::mock('PHPIMS\\Storage\\DriverInterface');

        Operation::factory('foobar', $database, $storage, 'GET', md5(microtime()));
    }

    public function testSetGetResource() {
        $resource = md5(microtime()) . '.png';
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setResource($resource));
        $this->assertSame($resource, $this->operation->getResource());
    }

    public function testSetGetPublicKey() {
        $key = md5(microtime());
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setPublicKey($key));
        $this->assertSame($key, $this->operation->getPublicKey());
    }

    public function testSetGetPrivateKey() {
        $key = md5(microtime());
        $this->assertInstanceOf('PHPIMS\\Operation', $this->operation->setPrivateKey($key));
        $this->assertSame($key, $this->operation->getPrivateKey());
    }
}
