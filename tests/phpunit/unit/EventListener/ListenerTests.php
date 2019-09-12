<?php
namespace ImboUnitTest\EventListener;

use Imbo\EventListener\ListenerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group listeners
 */
abstract class ListenerTests extends TestCase {
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
        $this->assertIsArray($events);

        foreach ($events as $event => $callbacks) {
            if (is_string($callbacks)) {
                $this->assertTrue(method_exists($listener, $callbacks), 'Method ' . $callbacks . ' does not exist in class ' . $className);
            } else {
                foreach ($callbacks as $method => $priority) {
                    $this->assertTrue(method_exists($listener, $method), 'Method ' . $method . ' does not exist in class ' . $className);
                    $this->assertIsInt($priority);
                }
            }
        }
    }
}
