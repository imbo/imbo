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

namespace Imbo\UnitTest\Resource;

use ReflectionClass,
    ReflectionMethod;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
