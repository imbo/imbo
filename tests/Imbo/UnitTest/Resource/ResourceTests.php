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
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
abstract class ResourceTests extends \PHPUnit_Framework_TestCase {
    /**
     * Return a resource that can be tested
     *
     * @return Imbo\Resource\ResourceInterface
     */
    abstract protected function getNewResource();

    /**
     * Make sure the that resource returns the correct methods for usage in the "Allow" header
     */
    public function testGetAllowedMethods() {
        $resource = $this->getNewResource();
        $reflection = new ReflectionClass($resource);
        $className = get_class($resource);
        $allHttpMethods = array(
            'get', 'post', 'put', 'delete', 'head',
        );

        $implementedMethods = array_filter($reflection->getMethods(ReflectionMethod::IS_PUBLIC), function($method) use($className, $allHttpMethods) {
            return $method->class == $className && (array_search($method->name, $allHttpMethods) !== false);
        });

        array_walk($implementedMethods, function(&$value, $key) { $value = strtoupper($value->name); });
        sort($implementedMethods);
        $expectedMethods = $resource->getAllowedMethods();
        sort($expectedMethods);

        $this->assertSame($expectedMethods, $implementedMethods);
    }

    public function testReturnsACorrectDefinition() {
        $definition = $this->getNewResource()->getDefinition();
        $this->assertInternalType('array', $definition);

        foreach ($definition as $d) {
            $this->assertInstanceOf('Imbo\EventListener\ListenerDefinition', $d);
        }
    }
}
