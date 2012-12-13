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

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\ListenerDefinition;

/**
 * @package TestSuite\UnitTests
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
