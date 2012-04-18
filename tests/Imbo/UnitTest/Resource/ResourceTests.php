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

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
abstract class ResourceTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Resource\ResourceInterface
     */
    protected $resource;

    /**
     * @var Imbo\Http\Request\RequestInterface
     */
    protected $request;

    /**
     * @var Imbo\Http\Response\ResponseInterface
     */
    protected $response;

    /**
     * @var Imbo\Database\DatabaseInterface
     */
    protected $database;

    /**
     * @var Imbo\Storage\StorageInterface
     */
    protected $storage;

    /**
     * Public key
     *
     * @var string
     */
    protected $publicKey = '84ddfea4384c0d2093540fbe6d8b13cb';

    /**
     * Image identifier
     *
     * @var string
     */
    protected $imageIdentifier = '34205548e02b8bd0865287a4b0b3fe5e';

    public function setUp() {
        $this->resource = $this->getNewResource();
        $this->request  = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage  = $this->getMock('Imbo\Storage\StorageInterface');
    }

    public function tearDown() {
        $this->resource = null;
        $this->request = null;
        $this->response = null;
        $this->database = null;
        $this->storage = null;
    }

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
        $reflection = new \ReflectionClass($this->resource);
        $className = get_class($this->resource);
        $allHttpMethods = array(
            'get', 'post', 'put', 'delete', 'head',
        );

        $implementedMethods = array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), function($method) use($className, $allHttpMethods) {
            return $method->class == $className && (array_search($method->name, $allHttpMethods) !== false);
        });

        array_walk($implementedMethods, function(&$value, $key) { $value = strtoupper($value->name); });
        sort($implementedMethods);
        $expectedMethods = $this->resource->getAllowedMethods();
        sort($expectedMethods);

        $this->assertSame($expectedMethods, $implementedMethods);
    }
}
