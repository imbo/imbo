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

use ReflectionClass,
    ReflectionMethod;

/**
 * @group unit
 * @group resources
 */
abstract class ResourceTests extends \PHPUnit_Framework_TestCase {
    /**
     * Return a resource that can be tested
     *
     * @return Imbo\Resource\ResourceInterface
     */
    abstract protected function getNewResource();

    public function testReturnsCorrectEventSubscriptions() {
        $className = get_class($this->getNewResource());
        $this->assertInternalType('array', $className::getSubscribedEvents());
    }

    public function testReturnsTheCorrectAllowedMethods() {
        $resource = $this->getNewResource();
        $shortName = strtolower(substr(get_class($resource), strrpos(get_class($resource), '\\') + 1));
        $methods = $resource->getAllowedMethods();
        $definition = $resource::getSubscribedEvents();

        foreach ($methods as $method) {
            $expectedEventName = strtolower($shortName . '.' . $method);

            foreach ($definition as $event => $callback) {
                if ($event === $expectedEventName) {
                    continue 2;
                }
            }

            $this->fail('Resource allows ' . $method . ', but no listener definition subscribes to ' . $expectedEventName);
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

            $this->fail('Resource subscribes to ' . $event . ' but does not allow ' . $expectedMethod);
        }
    }
}
