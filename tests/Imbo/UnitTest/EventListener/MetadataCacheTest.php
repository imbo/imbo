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

use Imbo\EventListener\MetadataCache,
    Imbo\Container;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\MetadataCache
 */
class MetadataCacheTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\EventListener\MetadataCache
     */
    private $listener;

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
     * @var Imbo\Cache\CacheInterface
     */
    private $cache;

    /**
     * @var Imbo\Http\Response\ResponseInterface
     */
    private $response;

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
     * @covers Imbo\EventListener\MetadataCache::__construct
     */
    public function setUp() {
        $this->publicKey = 'publicKey';
        $this->imageIdentifier = '7bf2e67f09de203da740a86cd37bbe8d';

        $this->cache = $this->getMock('Imbo\Cache\CacheInterface');
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');

        $this->container = new Container();
        $this->container->request = $this->request;
        $this->container->response= $this->response;

        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->event->expects($this->any())->method('getContainer')->will($this->returnValue($this->container));

        $this->listener = new MetadataCache($this->cache);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->cache = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->container = null;
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::invoke
     * @covers Imbo\EventListener\MetadataCache::getCacheKey
     */
    public function testListenerShouldHaltApplicationWhenACacheHitOccurs() {
        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $headers->expects($this->once())->method('set')->with('X-Imbo-MetadataCache', 'Hit');

        $cachedResponse = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $cachedResponse->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));

        $cacheKey = 'metadata:' . $this->publicKey . '|' . $this->imageIdentifier;

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('metadata.get.pre'));
        $this->event->expects($this->once())->method('haltApplication')->with(true);
        $this->cache->expects($this->once())->method('get')->with($cacheKey)->will($this->returnValue($cachedResponse));

        $this->listener->invoke($this->event);

        $this->assertSame($this->container->response, $cachedResponse);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::invoke
     * @covers Imbo\EventListener\MetadataCache::getCacheKey
     */
    public function testListenerShouldAddHeaderWhenCacheMissOccurs() {
        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $headers->expects($this->once())->method('set')->with('X-Imbo-MetadataCache', 'Miss');

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));

        $cacheKey = 'metadata:' . $this->publicKey . '|' . $this->imageIdentifier;

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('metadata.get.pre'));
        $this->cache->expects($this->once())->method('get')->with($cacheKey)->will($this->returnValue(null));

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::invoke
     * @covers Imbo\EventListener\MetadataCache::getCacheKey
     */
    public function testListenerShouldStoreResponseInCache() {
        $cacheKey = 'metadata:' . $this->publicKey . '|' . $this->imageIdentifier;

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('metadata.get.post'));
        $this->cache->expects($this->once())->method('set')->with($cacheKey, $this->response);
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::invoke
     * @covers Imbo\EventListener\MetadataCache::getCacheKey
     */
    public function testListenerShouldNotStoreResponseInCacheWhenResponseCodeIsNot200() {
        $cacheKey = 'metadata:' . $this->publicKey . '|' . $this->imageIdentifier;

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('metadata.get.post'));
        $this->cache->expects($this->never())->method('set');
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(304));

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::invoke
     * @covers Imbo\EventListener\MetadataCache::getCacheKey
     */
    public function testListenerShouldDeleteResponseFromCacheIfMetadataIsChanged() {
        $cacheKey = 'metadata:' . $this->publicKey . '|' . $this->imageIdentifier;

        $this->event->expects($this->exactly(3))->method('getName')->will($this->returnCallback(function() {
            static $counter = 0;

            $names = array(
                'metadata.delete.pre',
                'metadata.put.post',
                'metadata.post.post',
            );

            return $names[$counter++];
        }));

        $this->cache->expects($this->exactly(3))->method('delete')->with($cacheKey);

        $this->listener->invoke($this->event);
        $this->listener->invoke($this->event);
        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::getEvents
     */
    public function testListenerListensForSpecificEvents() {
        $this->assertSame(array(
            'metadata.get.pre',
            'metadata.get.post',
            'metadata.delete.pre',
            'metadata.put.post',
            'metadata.post.post',
        ), $this->listener->getEvents());
    }
}
