<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Cache;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
abstract class CacheTests extends \PHPUnit_Framework_TestCase {
    private $driver;

    abstract protected function getDriver();

    public function setUp() {
        $this->driver = $this->getDriver();
    }

    public function tearDown() {
        $this->driver = null;
    }

    public function getCacheData() {
        return array(
            array('key1', 'value'),
            array('key2', 123),
            array('key3', array(1, 2, 3)),
            array('key4', new \stdClass()),
        );
    }

    /**
     * @dataProvider getCacheData
     */
    public function testSetGetAndDelete($key, $value) {
        $this->assertFalse($this->driver->get($key));
        $this->driver->set($key, $value);
        $this->assertEquals($value, $this->driver->get($key));
        $this->assertTrue($this->driver->delete($key));
        $this->assertFalse($this->driver->get($key));
    }

    public function testIncrement() {
        $value = 1;
        $key = 'incrementKey';
        $this->assertFalse($this->driver->get($key));
        $this->assertFalse($this->driver->increment($key));
        $this->driver->set($key, $value);
        $this->assertSame(2, $this->driver->increment($key));
        $this->assertSame(12, $this->driver->increment($key, 10));
    }

    public function testDecrement() {
        $value = 10;
        $key = 'decrementKey';
        $this->assertFalse($this->driver->get($key));
        $this->assertFalse($this->driver->decrement($key));
        $this->driver->set($key, $value);
        $this->assertSame(9, $this->driver->decrement($key));
        // Make sure we don't go below zero
        $this->assertSame(0, $this->driver->decrement($key, 100));
        $this->assertSame(0, $this->driver->get($key));
    }
}
