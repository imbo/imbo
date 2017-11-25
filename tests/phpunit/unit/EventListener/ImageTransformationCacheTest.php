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

use Imbo\EventListener\ImageTransformationCache;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Http\Request\Request;
use Imbo\EventManager\Event;
use Imbo\Model\Image;
use Imbo\Model\Error;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @covers Imbo\EventListener\ImageTransformationCache
 * @coversDefaultClass Imbo\EventListener\ImageTransformationCache
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

    /**
     * @var Event
     */
    private $event;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var ParameterBag
     */
    private $query;

    /**
     * @var string
     */
    private $user = 'user';

    /**
     * @var string
     */
    private $imageIdentifier = '7bf2e67f09de203da740a86cd37bbe8d';


    /**
     * @var ResponseHeaderBag
     */
    private $responseHeaders;

    /**
     * @var HeaderBag
     */
    private $requestHeaders;

    /**
     * @var vfsStreamDirectory
     */
    private $cacheDir;

    /**
     * Set up the listener
     */
    public function setUp() {
        if (!class_exists(vfsStream::class)) {
            $this->markTestSkipped('This testcase requires mikey179/vfsStream to run');
        }

        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->requestHeaders = $this->createMock(HeaderBag::class);
        $this->query = $this->createMock(ParameterBag::class);

        $this->response = $this->createMock(Response::class);
        $this->response->headers = $this->responseHeaders;

        $this->request = $this->createConfiguredMock(Request::class, [
            'getUser' => $this->user,
            'getImageIdentifier' => $this->imageIdentifier,
        ]);
        $this->request->query = $this->query;
        $this->request->headers = $this->requestHeaders;

        $this->event = $this->createConfiguredMock(Event::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
        ]);

        $this->cacheDir = vfsStream::setup($this->path);

        $this->listener = new ImageTransformationCache(['path' => vfsStream::url($this->path)]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers ::loadFromCache
     * @covers ::getCacheDir
     * @covers ::getCacheKey
     * @covers ::getCacheFilePath
     */
    public function testChangesTheImageInstanceOnCacheHit() {
        $imageFromCache = $this->createMock(Image::class);
        $headersFromCache = $this->createMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $cachedData = serialize([
            'image' => $imageFromCache,
            'headers' => $headersFromCache,
        ]);

        $this->request->method('getUser')
                      ->willReturn($this->user);

        $this->request->method('getImageIdentifier')
                      ->willReturn($this->imageIdentifier);

        $this->request->method('getExtension')
                      ->willReturn('png');

        $this->requestHeaders->method('get')
                             ->with('Accept', '*/*')
                             ->willReturn(
                                 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                             );

        $this->query->method('get')
                    ->with('t')
                    ->willReturn(['thumbnail']);

        $this->response->expects($this->once())
                       ->method('setModel')
                       ->with($imageFromCache)
                       ->willReturnSelf();

        $this->event->expects($this->once())
                    ->method('stopPropagation');

        $dir = 'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/c/6';
        $file = 'bc6ffe312a5741a5705afe8639c08835';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, $cachedData);

        $this->listener->loadFromCache($this->event);

        $this->assertInstanceOf(ResponseHeaderBag::class, $this->response->headers);
    }

    /**
     * @covers ::loadFromCache
     * @covers ::getCacheDir
     * @covers ::getCacheKey
     * @covers ::getCacheFilePath
     */
    public function testRemovesCorruptCachedDataOnCacheHit() {
        $this->request->method('getUser')
                      ->willReturn($this->user);

        $this->request->method('getImageIdentifier')
                      ->willReturn($this->imageIdentifier);

        $this->request->method('getExtension')
                      ->willReturn('png');

        $this->requestHeaders->expects($this->once())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->willReturn(
                                 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                             );

        $this->query->expects($this->once())
                    ->method('get')
                    ->with('t')
                    ->willReturn(['thumbnail']);

        $dir = 'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/c/6';
        $file = 'bc6ffe312a5741a5705afe8639c08835';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, 'corrupt data');

        $this->assertTrue(file_exists($fullPath));
        $this->listener->loadFromCache($this->event);
        clearstatcache();
        $this->assertFalse(file_exists($fullPath));
    }

    /**
     * @covers ::loadFromCache
     * @covers ::getCacheDir
     * @covers ::getCacheKey
     * @covers ::getCacheFilePath
     */
    public function testAddsCorrectResponseHeaderOnCacheMiss() {
        $this->requestHeaders->expects($this->once())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->willReturn('*/*');

        $this->responseHeaders->expects($this->once())
                              ->method('set')
                              ->with('X-Imbo-TransformationCache', 'Miss');

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers ::storeInCache
     */
    public function testDoesNotStoreNonImageModelsInTheCache() {
        $this->response->expects($this->once())
                       ->method('getModel')
                       ->willReturn($this->createMock(Error::class));

        $this->request->expects($this->never())
                      ->method('getUser');

        $this->listener->storeInCache($this->event);
    }

    /**
     * @covers ::storeInCache
     * @covers ::getCacheDir
     * @covers ::getCacheKey
     * @covers ::getCacheFilePath
     */
    public function testStoresImageInCache() {
        $image = $this->createMock(Image::class);

        $this->response->expects($this->once())
                       ->method('getModel')
                       ->willReturn($this->createMock(Image::class));

        $this->requestHeaders->expects($this->once())
                             ->method('get')
                             ->with('Accept', '*/*')
                             ->willReturn('*/*');

        $cacheFile = 'vfs://cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/0/5/b0571fa001b22145f82750c84c6ddda4';

        $this->assertFalse(is_file($cacheFile));
        $this->listener->storeInCache($this->event);
        $this->assertTrue(is_file($cacheFile));

        $data = unserialize(file_get_contents($cacheFile));

        $this->assertEquals($image, $data['image']);
        $this->assertEquals($this->responseHeaders, $data['headers']);
    }

    /**
     * @covers ::storeInCache
     * @covers ::getCacheDir
     * @covers ::getCacheKey
     * @covers ::getCacheFilePath
     */
    public function testDoesNotStoreIfCachedVersionAlreadyExists() {
        // Reusing the same logic as this test
        $this->testChangesTheImageInstanceOnCacheHit();

        $this->response->expects($this->once())
                       ->method('getModel')
                       ->willReturn($this->createMock(Image::class));

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
     * @covers ::deleteFromCache
     * @covers ::getCacheDir
     * @covers ::rmdir
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
     * @covers ::__construct
     */
    public function testThrowsAnExceptionWhenPathIsMissingFromTheParameters() {
        $this->expectExceptionObject(new InvalidArgumentException(
            'The image transformation cache path is missing from the configuration',
            500
        ));
        new ImageTransformationCache([]);
    }

    /**
     * @covers ::__construct
     */
    public function testThrowsExceptionWhenCacheDirIsNotWritable() {
        $dir = new vfsStreamDirectory('dir', 0);
        $this->cacheDir->addChild($dir);
        $this->expectExceptionObject(new InvalidArgumentException(
            'Image transformation cache path is not writable by the webserver: vfs://cacheDir/dir',
            500
        ));
        new ImageTransformationCache(['path' => 'vfs://cacheDir/dir']);
    }

    /**
     * @covers ::__construct
     */
    public function testDoesNotTriggerWarningIfCachePathDoesNotExistAndParentIsWritable() {
        $this->assertNotNull(
            new ImageTransformationCache(['path' => 'vfs://cacheDir/some/dir/that/does/not/exist'])
        );
    }
}
