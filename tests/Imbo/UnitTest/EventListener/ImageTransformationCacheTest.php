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

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\ImageTransformationCache,
    org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamWrapper;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\ImageTransformationCache
 */
class ImageTransformationCacheTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\EventListener\ImageTransformationCache
     */
    private $listener;

    /**
     * @var string
     */
    private $path;

    /**
     * @var Imbo\EventManager\EventInterface
     */
    private $event;

    /**
     * @var Imbo\Container
     */
    private $container;

    /**
     * @var Imbo\Http\Request\RequestInterface
     */
    private $request;

    /**
     * @var Imbo\Http\Response\ResponseInterface
     */
    private $response;

    /**
     * @var Imbo\Http\ParameterContainerInterface
     */
    private $query;

    /**
     * @var Imbo\Http\ContentNegotiation
     */
    private $cn;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $imageIdentifier;

    /**
     * Set up method
     *
     * @covers Imbo\EventListener\ImageTransformationCache::__construct
     */
    public function setUp() {
        if (!class_exists('org\bovigo\vfs\vfsStream')) {
            $this->markTestSkipped('This testcase requires vfsStream to run');
        }

        $query = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $cn = $this->getMock('Imbo\Http\ContentNegotiation');

        $this->container = $this->getMock('Imbo\Container');
        $this->container->expects($this->any())->method('get')->will($this->returnCallback(function($key) use ($request, $response) {
            return $$key;
        }));

        $this->query = $query;
        $this->request = $request;
        $this->response = $response;
        $this->event = $event;
        $this->cn = $cn;

        $this->publicKey = 'publicKey';
        $this->imageIdentifier = '7bf2e67f09de203da740a86cd37bbe8d';

        $this->request->expects($this->any())->method('getQuery')->will($this->returnValue($this->query));
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->event->expects($this->any())->method('getContainer')->will($this->returnValue($this->container));

        $this->path = 'cacheDir';

        vfsStream::setup($this->path);
        $this->listener = new ImageTransformationCache(vfsStream::url($this->path), $this->cn);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->query = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->cn = null;
        $this->listener = null;
        $this->container = null;
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::invoke
     */
    public function testReturnIfCacheIsNotNeeded() {
        $this->event->expects($this->once())->method('getName')->will($this->returnValue('image.get.pre'));
        $this->request->expects($this->once())->method('hasTransformations')->will($this->returnValue(false));
        $this->assertNull($this->listener->invoke($this->event));
    }
}
