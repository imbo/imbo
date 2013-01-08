<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\MetadataCache;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\EventListener\MetadataCache
 */
class MetadataCacheTest extends ListenerTests {
    /**
     * @var MetadataCache
     */
    private $listener;

    private $event;
    private $request;
    private $cache;
    private $response;
    private $responseHeaders;
    private $publicKey = 'key';
    private $imageIdentifier = 'imageid';

    /**
     * Set up the listener
     *
     * @covers Imbo\EventListener\MetadataCache::__construct
     */
    public function setUp() {
        $this->cache = $this->getMock('Imbo\Cache\CacheInterface');
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->response->expects($this->any())->method('getHeaders')->will($this->returnValue($this->responseHeaders));
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->listener = new MetadataCache($this->cache);
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->cache = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::loadFromCache
     */
    public function testUpdatesResponseOnCacheHit() {
        $this->cache->expects($this->once())->method('get')->with($this->isType('string'))->will($this->returnValue(array(
            'lastModified' => 'some date',
            'metadata' => array('key' => 'value'),
        )));

        $this->responseHeaders->expects($this->at(0))->method('set')->with('X-Imbo-MetadataCache', 'Hit')->will($this->returnSelf());
        $this->responseHeaders->expects($this->at(1))->method('set')->with('Last-Modified', 'some date')->will($this->returnSelf());
        $this->response->expects($this->once())->method('setBody')->with(array('key' => 'value'))->will($this->returnSelf());

        $this->event->expects($this->once())->method('stopPropagation')->with(true);

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::loadFromCache
     */
    public function testDoesNotUpdateResponseWhenCacheDataIsInvalid() {
        $this->cache->expects($this->once())->method('get')->with($this->isType('string'))->will($this->returnValue('some data'));
        $this->responseHeaders->expects($this->at(0))->method('set')->with('X-Imbo-MetadataCache', 'Miss')->will($this->returnSelf());
        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::storeInCache
     */
    public function testStoresDataInCacheWhenResponseCodeIs200() {
        $lastModified = 'some date';
        $data = array('some' => 'value');

        $this->cache->expects($this->once())->method('set')->with($this->isType('string'), array(
            'lastModified' => $lastModified,
            'metadata' => $data,
        ));
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(200));
        $this->response->expects($this->once())->method('getLastModified')->will($this->returnValue($lastModified));
        $this->response->expects($this->once())->method('getBody')->will($this->returnValue($data));

        $this->listener->storeInCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::storeInCache
     */
    public function testDoesNotStoreDataInCacheWhenResponseCodeIsNot200() {
        $this->cache->expects($this->never())->method('set');
        $this->response->expects($this->once())->method('getStatusCode')->will($this->returnValue(404));

        $this->listener->storeInCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\MetadataCache::deleteFromCache
     * @covers Imbo\EventListener\MetadataCache::getCacheKey
     */
    public function testCanDeleteContentFromCache() {
        $this->cache->expects($this->once())->method('delete')->with('metadata:' . $this->publicKey . '|' . $this->imageIdentifier);
        $this->listener->deleteFromCache($this->event);
    }
}
