<?php declare(strict_types=1);
namespace Imbo\EventListener;

use PHPUnit\Framework\TestCase;

abstract class ListenerTests extends TestCase
{
    abstract protected function getListener();

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsCorrectEventSubscriptions(): void
    {
        $listener = $this->getListener();
        $className = get_class($listener);
        $events = $className::getSubscribedEvents();

        $this->assertIsArray($events);

        foreach ($events as $callbacks) {
            if (is_string($callbacks)) {
                $this->assertTrue(
                    method_exists($listener, $callbacks),
                    sprintf('Method %s does not exist in class %s', $callbacks, $className),
                );
            } else {
                foreach ($callbacks as $method => $priority) {
                    $this->assertTrue(
                        method_exists($listener, $method),
                        sprintf('Method %s does not exist in class %s', $method, $className),
                    );
                    $this->assertIsInt($priority);
                }
            }
        }
    }
}
