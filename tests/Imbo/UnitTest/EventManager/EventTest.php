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

namespace Imbo\UnitTest\EventManager;

use Imbo\EventManager\Event;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventManager\Event
 */
class EventTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\EventManager\Event::__construct
     * @covers Imbo\EventManager\Event::getName
     * @covers Imbo\EventManager\Event::getContainer
     */
    public function testEvent() {
        $name = 'some.event.name';
        $container = $this->getMock('Imbo\Container');

        $event = new Event($name, $container);

        $this->assertSame($name, $event->getName());
        $this->assertSame($container, $event->getContainer());
    }

    /**
     * @covers Imbo\EventManager\Event::propagationIsStopped
     * @covers Imbo\EventManager\Event::stopPropagation
     */
    public function testPropagationCanBeStopped() {
        $event = new Event('name', $this->getMock('Imbo\Container'));
        $this->assertFalse($event->propagationIsStopped());
        $this->assertSame($event, $event->stopPropagation(true));
        $this->assertTrue($event->propagationIsStopped());
        $this->assertSame($event, $event->stopPropagation(false));
        $this->assertFalse($event->propagationIsStopped());
    }

    /**
     * @covers Imbo\EventManager\Event::applicationIsHalted
     * @covers Imbo\EventManager\Event::haltApplication
     */
    public function testApplicationCanBeHalted() {
        $event = new Event('name', $this->getMock('Imbo\Container'));
        $this->assertFalse($event->applicationIsHalted());
        $this->assertSame($event, $event->haltApplication(true));
        $this->assertTrue($event->applicationIsHalted());
        $this->assertSame($event, $event->haltApplication(false));
        $this->assertFalse($event->applicationIsHalted());
    }

    /**
     * @covers Imbo\EventManager\Event::__construct
     * @covers Imbo\EventManager\Event::getParams
     */
    public function testCanUseParameters() {
        $params = array('some' => 'param');
        $event = new Event('name', $this->getMock('Imbo\Container'), $params);
        $this->assertSame($params, $event->getParams());
    }
}
