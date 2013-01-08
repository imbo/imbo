<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\ListenerDefinition;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\EventListener\ListenerDefinition
 */
class ListenerDefinitionTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ListenerDefinition
     */
    private $definition;

    private $eventName = 'event';
    private $callback;
    private $priority = 1;
    private $publicKeys = array('include' => array('user'));

    /**
     * Set up the definition
     *
     * @covers Imbo\EventListener\ListenerDefinition::__construct
     * @covers Imbo\EventListener\ListenerDefinition::setEventName
     * @covers Imbo\EventListener\ListenerDefinition::setCallback
     * @covers Imbo\EventListener\ListenerDefinition::setPriority
     * @covers Imbo\EventListener\ListenerDefinition::setPublicKeys
     */
    public function setUp() {
        $this->callback = function($event) {};
        $this->definition = new ListenerDefinition($this->eventName, $this->callback, $this->priority, $this->publicKeys);
    }

    /**
     * Tear down the definition
     */
    public function tearDown() {
        $this->definition = null;
    }

    /**
     * @covers Imbo\EventListener\ListenerDefinition::getEventName
     */
    public function testCanGetEventName() {
        $this->assertSame($this->eventName, $this->definition->getEventName());
    }

    /**
     * @covers Imbo\EventListener\ListenerDefinition::getCallback
     */
    public function testCanGetCallback() {
        $this->assertSame($this->callback, $this->definition->getCallback());
    }

    /**
     * @covers Imbo\EventListener\ListenerDefinition::getPriority
     */
    public function testCanGetPriority() {
        $this->assertSame($this->priority, $this->definition->getPriority());
    }

    /**
     * @covers Imbo\EventListener\ListenerDefinition::getPublicKeys
     */
    public function testCanGetPublicKeys() {
        $this->assertSame($this->publicKeys, $this->definition->getPublicKeys());
    }
}
