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

use Imbo\EventListener\ImageTransformationCache,
    org\bovigo\vfs\vfsStream;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class ImageTransformationCacheTest extends ListenerTests {
    /**
     * @var ImageTransformationCache
     */
    private $listener;

    /**
     * Cache path
     *
     * @var string
     */
    private $path = 'cacheDir';

    private $event;
    private $request;
    private $response;
    private $query;
    private $publicKey = 'publicKey';
    private $imageIdentifier = '7bf2e67f09de203da740a86cd37bbe8d';
    private $responseHeaders;
    private $cacheDir;

    /**
     * Set up the listener
     */
    public function setUp() {
        if (!class_exists('org\bovigo\vfs\vfsStream')) {
            $this->markTestSkipped('This testcase requires vfsStream to run');
        }

        $this->responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $this->query = $this->getMockBuilder('Imbo\Http\ParameterContainer')->disableOriginalConstructor()->getMock();
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->response->expects($this->any())->method('getHeaders')->will($this->returnValue($this->responseHeaders));
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getQuery')->will($this->returnValue($this->query));
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->cacheDir = vfsStream::setup($this->path);
        $this->listener = new ImageTransformationCache(vfsStream::url($this->path));
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->query = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->listener = null;
        $this->cacheDir = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     */
    public function testDoesNotLookInCacheIfNoTransformationsExists() {
        $image = $this->getMock('Imbo\Model\Image');
        $this->query->expects($this->once())->method('get')->with('t')->will($this->returnValue(null));
        $this->response->expects($this->never())->method('getImage');

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testChangesTheImageInstanceOnCacheHit() {
        $imageFromCache = $this->getMock('Imbo\Model\Image');
        $image = $this->getMock('Imbo\Model\Image');

        $this->query->expects($this->once())->method('get')->with('t')->will($this->returnValue(array('thumbnail')));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->response->expects($this->once())->method('setImage')->with($imageFromCache);

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->responseHeaders->expects($this->once())->method('set')->with('X-Imbo-TransformationCache', 'Hit');
        $this->event->expects($this->once())->method('stopPropagation')->with(true);

        $dir = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/c/9/5';
        $file = 'c955032a862e0c8e76c8e51f5f402a6cf4e908ce013027e4a1400bc8754dcadd';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, serialize($imageFromCache));

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testRemovesCorruptCachedDataOnCacheHit() {
        $imageFromCache = $this->getMock('Imbo\Model\Image');
        $image = $this->getMock('Imbo\Model\Image');

        $this->query->expects($this->once())->method('get')->with('t')->will($this->returnValue(array('thumbnail')));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->response->expects($this->never())->method('setImage');

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->responseHeaders->expects($this->once())->method('set')->with('X-Imbo-TransformationCache', 'Miss');

        $dir = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/c/9/5';
        $file = 'c955032a862e0c8e76c8e51f5f402a6cf4e908ce013027e4a1400bc8754dcadd';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, 'invalid data');

        $this->assertTrue(file_exists($fullPath));
        $this->listener->loadFromCache($this->event);
        $this->assertFalse(file_exists($fullPath));
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testAddsCorrectResponseHeaderOnCacheMiss() {
        $imageFromCache = $this->getMock('Imbo\Model\Image');
        $image = $this->getMock('Imbo\Model\Image');

        $this->query->expects($this->once())->method('get')->with('t')->will($this->returnValue(array('thumbnail')));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->responseHeaders->expects($this->once())->method('set')->with('X-Imbo-TransformationCache', 'Miss');

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::storeInCache
     */
    public function testDoesNotStoreImageInCacheIfNoTransformationHaveBeenApplied() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->will($this->returnValue(false));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->event->expects($this->never())->method('getRequest');

        $this->listener->storeInCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::storeInCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testStoresImageInCacheWhenTransformationsHaveBeenApplied() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->will($this->returnValue(true));
        $fileContents = serialize($image);

        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->query->expects($this->once())->method('get')->with('t')->will($this->returnValue(array('thumbnail')));

        $cacheFile = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/c/9/5/c955032a862e0c8e76c8e51f5f402a6cf4e908ce013027e4a1400bc8754dcadd';

        $this->assertFalse(is_file($cacheFile));
        $this->listener->storeInCache($this->event);
        $this->assertTrue(is_file($cacheFile));
        $this->assertEquals($image, unserialize(file_get_contents($cacheFile)));
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::deleteFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::rmdir
     */
    public function testCanDeleteAllImageVariationsFromCache() {
        $dir = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/f';
        $file = '30f0763c8422360d10fd84573dd582933a463e084b4f12b2b88eb1467e9eb338';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, 'image data');

        $this->assertTrue(is_file($fullPath));
        $this->listener->deleteFromCache($this->event);
        $this->assertFalse(is_file($fullPath));
        $this->assertFalse(is_dir('vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d'));
        $this->assertTrue(is_dir('vfs://cacheDir/p/u/b/publicKey/7/b/f'));
    }
}
