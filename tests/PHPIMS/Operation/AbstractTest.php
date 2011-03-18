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
class PHPIMS_Operation_AbstractTest extends PHPUnit_Framework_TestCase {
    /**
     * Operation instance
     *
     * @var PHPIMS_Operation_Abstract
     */
    protected $operation = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->operation = $this->getMockBuilder('PHPIMS_Operation_Abstract')
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();
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
        $driver = $this->getMockForAbstractClass('PHPIMS_Database_Driver_Abstract');
        $this->operation->setDatabase($driver);
        $this->assertSame($driver, $this->operation->getDatabase());
    }

    public function testSetGetStorage() {
        $driver = $this->getMockForAbstractClass('PHPIMS_Storage_Driver_Abstract');
        $this->operation->setStorage($driver);
        $this->assertSame($driver, $this->operation->getStorage());
    }

    public function testSetGetImage() {
        $image = $this->getMock('PHPIMS_Image');
        $this->operation->setImage($image);
        $this->assertSame($image, $this->operation->getImage());

    }

    public function testSetGetAndAddPlugins() {
        $plugin1 = $this->getMockForAbstractClass('PHPIMS_Operation_Plugin_Abstract');
        $plugin2 = $this->getMockForAbstractClass('PHPIMS_Operation_Plugin_Abstract');
        $plugin3 = $this->getMockForAbstractClass('PHPIMS_Operation_Plugin_Abstract');

        $this->operation->setPlugins(array($plugin1, $plugin2));
        $this->assertSame(array($plugin1, $plugin2), $this->operation->getPlugins());

        $this->operation->addPlugin($plugin3);
        $this->assertSame(array($plugin1, $plugin2, $plugin3), $this->operation->getPlugins());

        $this->operation->setPlugins(array($plugin3));
        $this->assertSame(array($plugin3), $this->operation->getPlugins());
    }

    public function testPreAndPostExec() {
        $plugin1 = $this->getMock('PHPIMS_Operation_Plugin_Abstract');
        $plugin1->expects($this->once())->method('preExec');
        $plugin1->expects($this->once())->method('postExec');

        $plugin2 = $this->getMock('PHPIMS_Operation_Plugin_Abstract');
        $plugin2->expects($this->once())->method('preExec');
        $plugin2->expects($this->once())->method('postExec');

        $this->operation->setPlugins(array($plugin1, $plugin2));
        $this->operation->preExec();
        $this->operation->postExec();
    }

    public function testPreExecWhenAPluginFails() {
        $plugin1 = $this->getMock('PHPIMS_Operation_Plugin_Abstract');
        $plugin1->expects($this->once())->method('preExec');

        $message = 'some message';
        $plugin2 = $this->getMock('PHPIMS_Operation_Plugin_Abstract');
        $plugin2->expects($this->once())->method('preExec')->will($this->throwException(new PHPIMS_Operation_Plugin_Exception($message)));

        $this->operation->setPlugins(array($plugin1, $plugin2));
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        $this->operation->preExec();
    }

    public function testPostExecWhenAPluginFails() {
        $plugin1 = $this->getMock('PHPIMS_Operation_Plugin_Abstract');
        $plugin1->expects($this->once())->method('postExec');

        $message = 'some message';
        $plugin2 = $this->getMock('PHPIMS_Operation_Plugin_Abstract');
        $plugin2->expects($this->once())->method('postExec')->will($this->throwException(new PHPIMS_Operation_Plugin_Exception($message)));

        $this->operation->setPlugins(array($plugin1, $plugin2));
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        $this->operation->postExec();
    }

    public function testInitMethod() {
        $database = $this->getMockForAbstractClass('PHPIMS_Database_Driver_Abstract');
        $databaseClassName = get_class($database);
        $databaseParams = array('someparam' => true, 'otherparam' => false);

        $storage = $this->getMockForAbstractClass('PHPIMS_Storage_Driver_Abstract');
        $storageClassName = get_class($storage);
        $storageParams = array('someparam' => false, 'otherparam' => true);

        $plugin = $this->getMockForAbstractClass('PHPIMS_Operation_Plugin_Abstract');
        $pluginClassName = get_class($plugin);
        $pluginParams = array('someparam' => true, 'someotherparam' => false);

        $internalPlugin = $this->getMockForAbstractClass('PHPIMS_Operation_Plugin_Abstract');
        $internalPluginClassName = get_class($internalPlugin);
        $internalPluginParams = array('someparam' => true, 'someotherparam' => false);

        $this->operation->setInternalPluginsSpec(array($internalPluginClassName => $internalPluginParams));

        $config = array(
            'database' => array(
                'driver' => $databaseClassName,
                'params' => $databaseParams,
            ),
            'storage' => array(
                'driver' => $storageClassName,
                'params' => $storageParams,
            ),
            'plugins' => array(
                'PHPIMS_Operation_Abstract' => array(
                    $pluginClassName => $pluginParams,
                ),
            ),
        );

        $this->operation->init($config);
        $this->assertInstanceOf($databaseClassName, $this->operation->getDatabase());
        $this->assertSame($databaseParams, $this->operation->getDatabase()->getParams());
        $this->assertInstanceOf($storageClassName, $this->operation->getStorage());
        $this->assertSame($storageParams, $this->operation->getStorage()->getParams());

        $plugins = $this->operation->getPlugins();
        $this->assertInstanceOf($pluginClassName, $plugins[0]);
        $this->assertSame($pluginParams, $plugins[0]->getParams());
    }

    public function testSetGetInternalPluginsSpec() {
        $spec = array(
            'ClassName' => array(),
        );
        $this->operation->setInternalPluginsSpec($spec);
        $this->assertSame($spec, $this->operation->getInternalPluginsSpec());
    }
}