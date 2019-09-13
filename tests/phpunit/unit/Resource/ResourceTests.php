<?php
namespace ImboUnitTest\Resource;

use Imbo\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

abstract class ResourceTests extends TestCase {
    /**
     * Return a resource that can be tested
     *
     * @return ResourceInterface
     */
    abstract protected function getNewResource();

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsCorrectEventSubscriptions() {
        $className = get_class($this->getNewResource());
        $this->assertIsArray($className::getSubscribedEvents());
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsTheCorrectAllowedMethods() {
        $resource = $this->getNewResource();

        // Translate the class name to an event name: Imbo\Resource\GlobalShortUrl => globalshorturl
        $shortName = strtolower(substr(get_class($resource), strrpos(get_class($resource), '\\') + 1));

        $methods = $resource->getAllowedMethods();
        $definition = $resource::getSubscribedEvents();

        $this->assertIsArray($definition);

        foreach ($methods as $method) {
            $expectedEventName = strtolower($shortName . '.' . $method);

            foreach ($definition as $event => $callback) {
                if ($event === $expectedEventName) {
                    continue 2;
                }
            }

            $this->fail(sprintf(
                'Resource allows %s, but no listener definition subscribes to %s',
                $method,
                $expectedEventName
            ));
        }

        foreach ($definition as $event => $callback) {
            if (strpos($event, $shortName) !== 0) {
                continue;
            }

            $expectedMethod = strtoupper(substr($event, strrpos($event, '.') + 1));

            foreach ($methods as $method) {
                if ($method === $expectedMethod) {
                    continue 2;
                }
            }

            $this->fail(sprintf(
                'Resource subscribes to %s but does not allow %s',
                $event,
                $expectedMethod
            ));
        }
    }
}
