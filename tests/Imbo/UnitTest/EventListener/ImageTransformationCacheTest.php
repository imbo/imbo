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
    org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamDirectory;

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
    private $requestHeaders;
    private $cacheDir;

    /**
     * Set up the listener
     */
    public function setUp() {
        if (!class_exists('org\bovigo\vfs\vfsStream')) {
            $this->markTestSkipped('This testcase requires vfsStream to run');
        }

        $this->responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $this->requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $this->query = $this->getMockBuilder('Imbo\Http\ParameterContainer')->disableOriginalConstructor()->getMock();
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->response->expects($this->any())->method('getHeaders')->will($this->returnValue($this->responseHeaders));
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getQuery')->will($this->returnValue($this->query));
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->any())->method('getHeaders')->will($this->returnValue($this->requestHeaders));
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
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testChangesTheImageInstanceOnCacheHit() {
        $imageFromCache = $this->getMock('Imbo\Model\Image');
        $headersFromCache = $this->getMock('Imbo\Http\HeaderContainer');
        $cachedData = serialize(array(
            'image' => $imageFromCache,
            'headers' => $headersFromCache,
        ));

        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $this->requestHeaders->expects($this->once())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->will($this->returnValue(
                                 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                             ));

        $this->query->expects($this->once())->method('get')->with('t')->will($this->returnValue(array('thumbnail')));

        $this->response->expects($this->once())->method('setModel')->with($imageFromCache)->will($this->returnSelf());
        $this->response->expects($this->once())->method('setHeaders')->with($headersFromCache)->will($this->returnSelf());;
        $this->event->expects($this->once())->method('stopPropagation')->with(true);

        $dir = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/6/7/7';
        $file = '677605632e7a57c58734e0a60cc1aaa7';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, $cachedData);

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testRemovesCorruptCachedDataOnCacheHit() {
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $this->requestHeaders->expects($this->once())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->will($this->returnValue(
                                 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                             ));

        $this->query->expects($this->once())->method('get')->with('t')->will($this->returnValue(array('thumbnail')));

        $dir = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/6/7/7';
        $file = '677605632e7a57c58734e0a60cc1aaa7';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, 'corrupt data');

        $this->assertTrue(file_exists($fullPath));
        $this->listener->loadFromCache($this->event);
        $this->assertFalse(file_exists($fullPath));
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testAddsCorrectResponseHeaderOnCacheMiss() {
        $this->requestHeaders->expects($this->once())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->will($this->returnValue('*/*'));
        $this->responseHeaders->expects($this->once())->method('set')->with('X-Imbo-TransformationCache', 'Miss');
        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::storeInCache
     */
    public function testDoesNotStoreNonImageModelsInTheCache() {
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->getMock('Imbo\Model\Error')));
        $this->request->expects($this->never())->method('getPublicKey');
        $this->listener->storeInCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::storeInCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testStoresImageInCache() {
        $image = $this->getMock('Imbo\Model\Image');

        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->getMock('Imbo\Model\Image')));
        $this->requestHeaders->expects($this->once())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->will($this->returnValue('*/*'));

        $cacheFile = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/5/a/a/5aa83f9df03e31b07c97299b927c6fd7';

        $this->assertFalse(is_file($cacheFile));
        $this->listener->storeInCache($this->event);
        $this->assertTrue(is_file($cacheFile));

        $data = unserialize(file_get_contents($cacheFile));

        $this->assertEquals($image, $data['image']);
        $this->assertEquals($this->responseHeaders, $data['headers']);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::deleteFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::rmdir
     */
    public function testCanDeleteAllImageVariationsFromCache() {
        $cachedFiles = array(
            'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/f/30f0763c8422360d10fd84573dd58293',
            'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/e/30e0763c8422360d10fd84573dd58293',
            'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/d/30d0763c8422360d10fd84573dd58293',
        );

        foreach ($cachedFiles as $file) {
            @mkdir(dirname($file), 0775, true);
            file_put_contents($file, 'image data');
            $this->assertTrue(is_file($file));
        }

        $this->listener->deleteFromCache($this->event);

        foreach ($cachedFiles as $file) {
            $this->assertFalse(is_file($file));
        }

        $this->assertFalse(is_dir('vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d'));
        $this->assertTrue(is_dir('vfs://cacheDir/p/u/b/publicKey/7/b/f'));
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Cache path is not writable by the webserver
     * @covers Imbo\EventListener\ImageTransformationCache::__construct
     */
    public function testTriggersWarningIfCacheDirIsNotWritable() {
        $dir = new vfsStreamDirectory('dir', 0);
        $this->cacheDir->addChild($dir);

        $listener = new ImageTransformationCache('vfs://cacheDir/dir');
    }
}
