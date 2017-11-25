<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Resource;

use ReflectionClass;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group resources
 */
abstract class ResourceTests extends TestCase {
    /**
     * Return a resource that can be tested
     *
     * @return Imbo\Resource\ResourceInterface
     */
    abstract protected function getNewResource();

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsCorrectEventSubscriptions() {
        $className = get_class($this->getNewResource());
        $this->assertInternalType('array', $className::getSubscribedEvents());
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

        $this->assertInternalType('array', $definition);

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
