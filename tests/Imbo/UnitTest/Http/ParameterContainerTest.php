<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http;

use Imbo\Http\ParameterContainer;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\Http\ParameterContainer
 */
class ParameterContainerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ParameterContainer
     */
    private $container;

    private $parameters = array(
        'key1' => 'value1',
        'key2' => 'value2',
        'KEY1' => 'VALUE1',
        'KEY2' => 'VALUE2',
    );

    /**
     * Set up the container
     */
    public function setUp() {
        $this->container = new ParameterContainer($this->parameters);
    }

    /**
     * Tear down the container
     */
    public function tearDown() {
        $this->container = null;
    }

    /**
     * @covers Imbo\Http\ServerContainer::getAll
     */
    public function testCanGetAllParameters() {
        $this->assertSame($this->parameters, $this->container->getAll());
    }

    /**
     * @covers Imbo\Http\ServerContainer::has
     * @covers Imbo\Http\ServerContainer::remove
     */
    public function testCanRemoveValues() {
        $this->assertTrue($this->container->has('key1'));
        $this->assertSame($this->container, $this->container->remove('key1'));
        $this->assertFalse($this->container->has('key1'));
    }

    /**
     * @covers Imbo\Http\ServerContainer::set
     * @covers Imbo\Http\ServerContainer::get
     */
    public function testCanSetAndGetValues() {
        $this->assertSame($this->container, $this->container->set('key', 'value'));
        $this->assertSame('value', $this->container->get('key'));

        return $this->container;
    }

    /**
     * @depends testCanSetAndGetValues
     * @covers Imbo\Http\ServerContainer::removeAll
     * @covers Imbo\Http\ServerContainer::getAll
     */
    public function testRemoveAll($container) {
        $this->assertNotEmpty($container->getAll());
        $this->assertSame($container, $container->removeAll());
        $this->assertEmpty($container->getAll());
    }

    /**
     * Fetch different parameters
     *
     * @return array[]
     */
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
    public function testCanRepresentItselfAsAString(array $params, $expected) {
        $container = new ParameterContainer($params);
        $this->assertSame($expected, $container->asString());
    }
}
