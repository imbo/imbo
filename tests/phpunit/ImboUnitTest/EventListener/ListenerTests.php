<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener;

use Imbo\EventListener\ListenerInterface;

/**
 * @group unit
 * @group listeners
 */
abstract class ListenerTests extends \PHPUnit_Framework_TestCase {
    /**
     * Get the listener we are testing
     *
     * @return ListenerInterface
     */
    abstract protected function getListener();

    public function testReturnsCorrectEventSubscriptions() {
        $listener = $this->getListener();
        $className = get_class($listener);
        $events = $className::getSubscribedEvents();
        $this->assertInternalType('array', $events);

        foreach ($events as $event => $callbacks) {
            if (is_string($callbacks)) {
                $this->assertTrue(method_exists($listener, $callbacks), 'Method ' . $callbacks . ' does not exist in class ' . $className);
            } else {
                foreach ($callbacks as $method => $priority) {
                    $this->assertTrue(method_exists($listener, $method), 'Method ' . $method . ' does not exist in class ' . $className);
                    $this->assertInternalType('integer', $priority);
                }
            }
        }
    }
}
