<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener;

use Imbo\EventListener\ImageTransformationCache,
    org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamDirectory;

/**
 * @covers Imbo\EventListener\ImageTransformationCache
 * @group unit
 * @group listeners
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
    private $user = 'user';
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

        $this->responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $this->requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->query = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');

        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->headers = $this->responseHeaders;

        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->request->query = $this->query;
        $this->request->headers = $this->requestHeaders;
        $this->request->expects($this->any())->method('getUser')->will($this->returnValue($this->user));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->cacheDir = vfsStream::setup($this->path);
        $this->listener = new ImageTransformationCache(['path' => vfsStream::url($this->path)]);
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
        $headersFromCache = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $cachedData = serialize([
            'image' => $imageFromCache,
            'headers' => $headersFromCache,
        ]);

        $this->request->expects($this->any())->method('getUser')->will($this->returnValue($this->user));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $this->requestHeaders->expects($this->any())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->will($this->returnValue(
                                 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                             ));

        $this->query->expects($this->any())->method('get')->with('t')->will($this->returnValue(['thumbnail']));

        $this->response->expects($this->once())->method('setModel')->with($imageFromCache)->will($this->returnSelf());
        $this->event->expects($this->once())->method('stopPropagation');

        $dir = 'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/c/6';
        $file = 'bc6ffe312a5741a5705afe8639c08835';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, $cachedData);

        $this->listener->loadFromCache($this->event);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\ResponseHeaderBag', $this->response->headers);

        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testRemovesCorruptCachedDataOnCacheHit() {
        $this->request->expects($this->any())->method('getUser')->will($this->returnValue($this->user));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $this->requestHeaders->expects($this->once())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->will($this->returnValue(
                                 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                             ));

        $this->query->expects($this->once())->method('get')->with('t')->will($this->returnValue(['thumbnail']));

        $dir = 'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/c/6';
        $file = 'bc6ffe312a5741a5705afe8639c08835';
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
        $this->request->expects($this->never())->method('getUser');
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

        $cacheFile = 'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/0/5/b0571fa001b22145f82750c84c6ddda4';

        $this->assertFalse(is_file($cacheFile));
        $this->listener->storeInCache($this->event);
        $this->assertTrue(is_file($cacheFile));

        $data = unserialize(file_get_contents($cacheFile));

        $this->assertEquals($image, $data['image']);
        $this->assertEquals($this->responseHeaders, $data['headers']);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::storeInCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testDoesNotStoreIfCachedVersionAlreadyExists() {
        // Reusing the same logic as this test
        $this->testChangesTheImageInstanceOnCacheHit();

        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($this->getMock('Imbo\Model\Image')));

        // Overwrite cached file
        $dir = 'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/c/6';
        $file = 'bc6ffe312a5741a5705afe8639c08835';
        $fullPath = $dir . '/' . $file;

        file_put_contents($fullPath, 'foobar');

        // Since we hit a cached version earlier, we shouldn't overwrite the cached file
        $this->listener->storeInCache($this->event);

        $this->assertEquals('foobar', file_get_contents($fullPath));
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::deleteFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheDir
     * @covers Imbo\EventListener\ImageTransformationCache::rmdir
     */
    public function testCanDeleteAllImageVariationsFromCache() {
        $cachedFiles = [
            'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/f/30f0763c8422360d10fd84573dd58293',
            'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/e/30e0763c8422360d10fd84573dd58293',
            'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/d/30d0763c8422360d10fd84573dd58293',
        ];

        foreach ($cachedFiles as $file) {
            @mkdir(dirname($file), 0775, true);
            file_put_contents($file, 'image data');
            $this->assertTrue(is_file($file));
        }

        $this->listener->deleteFromCache($this->event);

        foreach ($cachedFiles as $file) {
            $this->assertFalse(is_file($file));
        }

        $this->assertFalse(is_dir('vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d'));
        $this->assertTrue(is_dir('vfs://cacheDir/u/s/e/user/7/b/f'));
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage The image transformation cache path is missing from the configuration
     * @expectedExceptionCode 500
     * @covers Imbo\EventListener\ImageTransformationCache::__construct
     */
    public function testThrowsAnExceptionWhenPathIsMissingFromTheParameters() {
        $listener = new ImageTransformationCache([]);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Image transformation cache path is not writable by the webserver: vfs://cacheDir/dir
     * @expectedExceptionCode 500
     * @covers Imbo\EventListener\ImageTransformationCache::__construct
     */
    public function testThrowsExceptionWhenCacheDirIsNotWritable() {
        $dir = new vfsStreamDirectory('dir', 0);
        $this->cacheDir->addChild($dir);

        $listener = new ImageTransformationCache(['path' => 'vfs://cacheDir/dir']);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::__construct
     */
    public function testDoesNotTriggerWarningIfCachePathDoesNotExistAndParentIsWritable() {
        $listener = new ImageTransformationCache(['path' => 'vfs://cacheDir/some/dir/that/does/not/exist']);
    }
}
