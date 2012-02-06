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

namespace Imbo\UnitTest\Http;

use Imbo\Http\ParameterContainer;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Http\ParameterContainer
 */
class ParameterContainerTest extends \PHPUnit_Framework_TestCase {
    private $container;
    private $parameters;

    public function setUp() {
        $this->parameters = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'KEY1' => 'VALUE1',
            'KEY2' => 'VALUE2',
        );
        $this->container = new ParameterContainer($this->parameters);
    }

    public function tearDown() {
        $this->container = null;
        $this->parameters = null;
    }

    /**
     * @covers Imbo\Http\ServerContainer::getAll
     */
    public function testContainer() {
        $this->assertSame($this->parameters, $this->container->getAll());
    }

    /**
     * @covers Imbo\Http\ServerContainer::has
     * @covers Imbo\Http\ServerContainer::remove
     */
    public function testRemoveKey() {
        $this->assertTrue($this->container->has('key1'));
        $this->assertSame($this->container, $this->container->remove('key1'));
        $this->assertFalse($this->container->has('key1'));
    }

    /**
     * @covers Imbo\Http\ServerContainer::set
     * @covers Imbo\Http\ServerContainer::get
     */
    public function testSetAndGet() {
        $this->assertSame($this->container, $this->container->set('key', 'value'));
        $this->assertSame('value', $this->container->get('key'));
    }

    /**
     * @depends testSetAndGet
     * @covers Imbo\Http\ServerContainer::removeAll
     * @covers Imbo\Http\ServerContainer::getAll
     */
    public function testRemoveAll() {
        $this->assertSame($this->container, $this->container->removeAll());
        $this->assertEmpty($this->container->getAll());
    }

    public function getParameters() {
        return array(
            array(array('foo' => '', 'bar' => 'foo'), 'foo=&bar=foo'),
            array(array('foo' => 'bar', 'bar' => 'foo'), 'foo=bar&bar=foo'),
            array(array('key' => 'value', 'keys' => array(1, 2, 3, 'four'), 'foo' => 'bar'), 'key=value&keys[]=1&keys[]=2&keys[]=3&keys[]=four&foo=bar'),
        );
    }

    /**
     * @dataProvider getParameters
     * @covers Imbo\Http\ParameterContainer::asString
     */
    public function testAsString(array $params, $expected) {
        $container = new ParameterContainer($params);
        $this->assertSame($expected, $container->asString());
    }
}
