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

require 'Operation/_pluginsWithoutPrefix/CustomPlugin.php';
require 'Operation/_pluginsWithoutPrefix/OtherCustomPlugin.php';
require 'Operation/_pluginsWithPrefix/Some/Prefix/CustomPlugin.php';
require 'Operation/_pluginsWithPrefix/Some/Prefix/OtherCustomPlugin.php';

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
    protected $operation = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->operation = $this->getMockBuilder('PHPIMS\\Operation')->setMethods(array('getOperationName', 'exec', 'getRequestPath'))
                                ->disableOriginalConstructor()
                                ->getMock();

        // Make the operation return "addImage" as if it was the PHPIMS\Operation\AddImage
        // operation class
        $this->operation->expects($this->any())
                        ->method('getOperationName')
                        ->will($this->returnValue('addImage'));

        $this->operation->expects($this->any())
                        ->method('exec');

        $this->operation->expects($this->any())
                        ->method('getRequestPath');
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->operation = null;
    }

    public function testSetGetImageIdentifier() {
        $imageIdentifier = md5(time()) . '.png';
        $this->operation->setImageIdentifier($imageIdentifier);
        $this->assertSame($imageIdentifier, $this->operation->getImageIdentifier());
    }

    public function testSetGetDatabase() {
        $driver = m::mock('PHPIMS\\Database\\DriverInterface');
        $this->operation->setDatabase($driver);
        $this->assertSame($driver, $this->operation->getDatabase());
    }

    public function testSetGetStorage() {
        $driver = m::mock('PHPIMS\\Storage\\DriverInterface');
        $this->operation->setStorage($driver);
        $this->assertSame($driver, $this->operation->getStorage());
    }

    public function testSetGetImage() {
        $image = $this->getMock('PHPIMS\\Image');
        $this->operation->setImage($image);
        $this->assertSame($image, $this->operation->getImage());

    }

    public function testSetGetResponse() {
        $response = $this->getMock('PHPIMS\\Server\\Response');
        $this->operation->setResponse($response);
        $this->assertSame($response, $this->operation->getResponse());
    }

    public function testSetGetMethod() {
        $method = 'DELETE';
        $this->operation->setMethod($method);
        $this->assertSame($method, $this->operation->getMethod());
    }

    public function testInitPlugins() {
        // Add a directory that has no custom plugins and a directory that has some plugins
        $config = array(
            array(
                'path' => '/some/path',
                'prefix' => 'Some\\Prefix',
            ),
            array(
                'path' => __DIR__ . '/Operation/_pluginsWithPrefix',
                'prefix' => 'Some\\Prefix\\',
            ),
            array(
                'path' => __DIR__ . '/Operation/_pluginsWithoutPrefix',
            ),
        );

        $reflection = new \ReflectionClass($this->operation);
        $method = $reflection->getMethod('initPlugins');
        $method->setAccessible(true);
        $method->invokeArgs($this->operation, array($config));

        $reflection = new \ReflectionClass($this->operation);
        $method = $reflection->getMethod('getPlugins');
        $method->setAccessible(true);

        $plugins = $method->invoke($this->operation);

        $this->assertInstanceOf('Some\\Prefix\\CustomPlugin', $plugins['preExec'][1]);
        $this->assertInstanceOf('CustomPlugin', $plugins['preExec'][10]);
        $this->assertInstanceOf('OtherCustomPlugin', $plugins['preExec'][12]);
        $this->assertInstanceOf('Some\\Prefix\\OtherCustomPlugin', $plugins['preExec'][42]);
        $this->assertInstanceOf('PHPIMS\\Operation\\Plugin\\AuthPlugin', $plugins['preExec'][100]);
        $this->assertInstanceOf('PHPIMS\\Operation\\Plugin\\PrepareImagePlugin', $plugins['preExec'][101]);
        $this->assertInstanceOf('PHPIMS\\Operation\\Plugin\\IdentifyImagePlugin', $plugins['preExec'][102]);

        $this->assertInstanceOf('Some\\Prefix\\CustomPlugin', $plugins['postExec'][1]);
        $this->assertInstanceOf('OtherCustomPlugin', $plugins['postExec'][8]);
        $this->assertInstanceOf('CustomPlugin', $plugins['postExec'][20]);
        $this->assertInstanceOf('Some\\Prefix\\OtherCustomPlugin', $plugins['postExec'][78]);
    }

    public function testPreAndPostExec() {
        $plugin1 = m::mock('PHPIMS\\Operation\\PluginInterface');
        $plugin1->shouldReceive('exec')->times(2);

        $plugin2 = m::mock('PHPIMS\\Operation\\PluginInterface');
        $plugin2->shouldReceive('exec')->times(2);

        $plugin3 = m::mock('PHPIMS\\Operation\\PluginInterface');
        $plugin3->shouldReceive('exec')->once();

        $plugin4 = m::mock('PHPIMS\\Operation\\PluginInterface');
        $plugin4->shouldReceive('exec')->once();

        $plugins = array(
            'preExec' => array(
                1 => $plugin1,
                2 => $plugin2,
                3 => $plugin4,
            ),
            'postExec' => array(
                1 => $plugin1,
                2 => $plugin2,
                3 => $plugin3,
            ),
        );

        $reflection = new \ReflectionClass($this->operation);
        $method = $reflection->getMethod('setPlugins');
        $method->setAccessible(true);

        $method->invokeArgs($this->operation, array($plugins));

        $this->operation->preExec();
        $this->operation->postExec();
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

        foreach ($operations as $method => $className) {
            $this->assertInstanceOf($className, Operation::factory($className, $method, md5(microtime())));
        }
    }

    /**
     * @expectedException PHPIMS\Operation\Exception
     */
    public function testFactoryWithUnSupportedOperation() {
        Operation::factory('foobar', 'GET', md5(microtime()));
    }
}