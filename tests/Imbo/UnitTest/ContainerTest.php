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
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest;

use Imbo\Container;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Container
 */
class ContainerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Container
     */
    private $container;

    /**
     * Set up the container
     */
    public function setUp() {
        $this->container = new Container();
    }

    /**
     * Tear down the container
     */
    public function tearDown() {
        $this->container = null;
    }

    /**
     * Fetch different values
     *
     * @return array[]
     */
    public function getContainerValues() {
        return array(
            array('key1', 'value1', 'value1'),
            array('key2', 'value2', 'value2'),
            array('key3', function($container) { return 'value3'; }, 'value3'),
        );
    }

    /**
     * @covers Imbo\Container::set
     * @covers Imbo\Container::get
     * @dataProvider getContainerValues()
     */
    public function testCanSetAndGetValues($key, $value, $expected) {
        $this->container->set($key, $value);
        $this->assertSame($expected, $this->container->get($key));
    }

    /**
     * @covers Imbo\Container::has
     * @covers Imbo\Container::set
     */
    public function testCanCheckIfItHasAValueWithAGivenKey() {
        $this->assertFalse($this->container->has('key'));
        $this->container->set('key', 'value');
        $this->assertTrue($this->container->has('key'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @covers Imbo\Container::get
     */
    public function testThrowsAnExceptionWhenFetchingAKeyThatDoesNotExist() {
        $this->container->get('foobar');
    }

    /**
     * @covers Imbo\Container::get
     */
    public function testCanGenerateNewInstancesWhenFetching() {
        $this->container->set('key', function($container) { return new \stdClass(); });

        // Make sure that we don't get the same instance when referencing the key more than once
        $this->assertNotSame($this->container->get('key'), $this->container->get('key'));
    }

    /**
     * @covers Imbo\Container::setStatic
     */
    public function testCanReuseInstancesWhenFetching() {
        $this->container->setStatic('key', function($container) { return new \stdClass(); });

        // Make sure that we get the same instance when referencing the key more than once
        $this->assertSame($this->container->get('key'), $this->container->get('key'));
    }
}
