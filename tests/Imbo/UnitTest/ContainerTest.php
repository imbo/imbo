<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest;

use Imbo\Container;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
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
