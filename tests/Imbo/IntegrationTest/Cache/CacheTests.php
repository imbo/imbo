<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\IntegrationTest\Cache;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
    }
}
