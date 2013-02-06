<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Resource;

use ReflectionClass,
    ReflectionMethod;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
abstract class ResourceTests extends \PHPUnit_Framework_TestCase {
    /**
     * Return a resource that can be tested
     *
     * @return Imbo\Resource\ResourceInterface
     */
    abstract protected function getNewResource();

    public function testReturnsACorrectDefinition() {
        $definition = $this->getNewResource()->getDefinition();
        $this->assertInternalType('array', $definition);

        foreach ($definition as $d) {
            $this->assertInstanceOf('Imbo\EventListener\ListenerDefinition', $d);
        }
    }

    public function testReturnsTheCorrectAllowedMethods() {
        $resource = $this->getNewResource();
        $methods = $resource->getAllowedMethods();
        $definition = $resource->getDefinition();

        foreach ($methods as $method) {
            $expectedEventName = strtolower(substr(get_class($resource), strrpos(get_class($resource), '\\') + 1) . '.' . $method);

            foreach ($definition as $d) {
                $eventName = $d->getEventName();

                if ($eventName === $expectedEventName) {
                    continue 2;
                }
            }

            $this->fail('Resource allows ' . $method . ', but no listener definition subscribes to ' . $expectedEventName);
        }

        foreach ($definition as $d) {
            $eventName = $d->getEventName();
            $expectedMethod = strtoupper(substr($eventName, strrpos($eventName, '.') + 1));

            foreach ($methods as $method) {
                if ($method === $expectedMethod) {
                    continue 2;
                }
            }

            $this->fail('Resource subscribes to ' . $eventName . ' but does not allow ' . $expectedMethod);
        }
    }
}
