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
class PHPIMS_OperationTest extends PHPUnit_Framework_TestCase {
    /**
     * Operation instance
     *
     * @var PHPIMS_Operation
     */
    protected $operation = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->operation = $this->getMockBuilder('PHPIMS_Operation')->setMethods(array('getOperationName', 'exec', 'getRequestPath'))
                                ->disableOriginalConstructor()
                                ->getMock();

        // Make the operation return "addImage" as if it was the PHPIMS_Operation_AddImage
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

    public function testSetGetHash() {
        $hash = md5(time());
        $this->operation->setHash($hash);
        $this->assertSame($hash, $this->operation->getHash());
    }

    public function testSetGetDatabase() {
        $driver = $this->getMockForAbstractClass('PHPIMS_Database_Driver');
        $this->operation->setDatabase($driver);
        $this->assertSame($driver, $this->operation->getDatabase());
    }

    public function testSetGetStorage() {
        $driver = $this->getMockForAbstractClass('PHPIMS_Storage_Driver');
        $this->operation->setStorage($driver);
        $this->assertSame($driver, $this->operation->getStorage());
    }

    public function testSetGetImage() {
        $image = $this->getMock('PHPIMS_Image');
        $this->operation->setImage($image);
        $this->assertSame($image, $this->operation->getImage());

    }

    public function testSetGetResponse() {
        $response = $this->getMock('PHPIMS_Server_Response');
        $this->operation->setResponse($response);
        $this->assertSame($response, $this->operation->getResponse());
    }

    public function testSetGetMethod() {
        $method = 'DELETE';
        $this->operation->setMethod($method);
        $this->assertSame($method, $this->operation->getMethod());
    }

    public function testInitDatabaseDriver() {
        $database = $this->getMockForAbstractClass('PHPIMS_Database_Driver');
        $databaseClassName = get_class($database);
        $databaseParams = array('someparam' => true, 'otherparam' => false);

        $config = array(
            'driver' => $databaseClassName,
            'params' => $databaseParams,
        );

        $reflection = new ReflectionClass($this->operation);
        $method = $reflection->getMethod('initDatabaseDriver');
        $method->setAccessible(true);

        $method->invokeArgs($this->operation, array($config));

        $this->assertInstanceOf($databaseClassName, $this->operation->getDatabase());
        $this->assertSame($databaseParams, $this->operation->getDatabase()->getParams());
    }

    public function testInitStorageDriver() {
        $storage = $this->getMockForAbstractClass('PHPIMS_Storage_Driver');
        $storageClassName = get_class($storage);
        $storageParams = array('someparam' => false, 'otherparam' => true);

        $config = array(
            'driver' => $storageClassName,
            'params' => $storageParams,
        );

        $reflection = new ReflectionClass($this->operation);
        $method = $reflection->getMethod('initStorageDriver');
        $method->setAccessible(true);

        $method->invokeArgs($this->operation, array($config));

        $this->assertInstanceOf($storageClassName, $this->operation->getStorage());
        $this->assertSame($storageParams, $this->operation->getStorage()->getParams());
    }

    public function testInitPlugins() {
        // Add a directory that has no custom plugins and a directory that has some plugins
        $config = array(
            array(
                'path' => '/some/path',
                'prefix' => 'Some_Prefix',
            ),
            array(
                'path' => __DIR__ . '/Operation/_pluginsWithPrefix',
                'prefix' => 'Some_Prefix_',
            ),
            array(
                'path' => __DIR__ . '/Operation/_pluginsWithoutPrefix',
            ),
        );

        $reflection = new ReflectionClass($this->operation);
        $method = $reflection->getMethod('initPlugins');
        $method->setAccessible(true);
        $method->invokeArgs($this->operation, array($config));

        $reflection = new ReflectionClass($this->operation);
        $method = $reflection->getMethod('getPlugins');
        $method->setAccessible(true);

        $plugins = $method->invoke($this->operation);

        $this->assertInstanceOf('Some_Prefix_CustomPlugin', $plugins['preExec'][1]);
        $this->assertInstanceOf('CustomPlugin', $plugins['preExec'][10]);
        $this->assertInstanceOf('OtherCustomPlugin', $plugins['preExec'][12]);
        $this->assertInstanceOf('Some_Prefix_OtherCustomPlugin', $plugins['preExec'][42]);
        $this->assertInstanceOf('PHPIMS_Operation_Plugin_AuthPlugin', $plugins['preExec'][100]);
        $this->assertInstanceOf('PHPIMS_Operation_Plugin_PrepareImagePlugin', $plugins['preExec'][101]);
        $this->assertInstanceOf('PHPIMS_Operation_Plugin_IdentifyImagePlugin', $plugins['preExec'][102]);

        $this->assertInstanceOf('Some_Prefix_CustomPlugin', $plugins['postExec'][1]);
        $this->assertInstanceOf('OtherCustomPlugin', $plugins['postExec'][8]);
        $this->assertInstanceOf('CustomPlugin', $plugins['postExec'][20]);
        $this->assertInstanceOf('Some_Prefix_OtherCustomPlugin', $plugins['postExec'][78]);
    }

    public function ttestPreAndPostExec() {
        $plugin1 = $this->getMockBuilder('PHPIMS_Operation_Plugin_Abstract')->disableOriginalConstructor()->getMockForAbstractClass();
        $plugin1->expects($this->exactly(2))->method('exec');

        $plugin2 = $this->getMockBuilder('PHPIMS_Operation_Plugin_Abstract')->disableOriginalConstructor()->getMockForAbstractClass();
        $plugin2->expects($this->exactly(2))->method('exec');

        $plugin3 = $this->getMockBuilder('PHPIMS_Operation_Plugin_Abstract')->disableOriginalConstructor()->getMockForAbstractClass();
        $plugin3->expects($this->once())->method('exec');

        $plugin4 = $this->getMockBuilder('PHPIMS_Operation_Plugin_Abstract')->disableOriginalConstructor()->getMockForAbstractClass();
        $plugin4->expects($this->once())->method('exec');

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

        $reflection = new ReflectionClass($this->operation);
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
            'POST'   => 'PHPIMS_Operation_AddImage',
            'POST'   => 'PHPIMS_Operation_EditMetadata',
            'DELETE' => 'PHPIMS_Operation_DeleteImage',
            'DELETE' => 'PHPIMS_Operation_DeleteMetadata',
            'GET'    => 'PHPIMS_Operation_GetImage',
            'GET'    => 'PHPIMS_Operation_GetMetadata',
        );

        foreach ($operations as $method => $className) {
            $this->assertInstanceOf($className, PHPIMS_Operation::factory($className, $method, md5(microtime())));
        }
    }

    /**
     * @expectedException PHPIMS_Operation_Exception
     */
    public function testFactoryWithUnSupportedOperation() {
        PHPIMS_Operation::factory('foobar', 'GET', md5(microtime()));
    }
}