<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\Event;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Error;
use Imbo\Model\Image;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use TestFs\StreamWrapper as TestFs;

/**
 * @coversDefaultClass Imbo\EventListener\ImageTransformationCache
 */
class ImageTransformationCacheTest extends ListenerTests
{
    private $listener;
    private $event;
    private $request;
    private $response;
    private $query;
    private $user = 'user';
    private $imageIdentifier = '7bf2e67f09de203da740a86cd37bbe8d';
    private $responseHeaders;
    private $requestHeaders;

    public function setUp(): void
    {
        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->requestHeaders = $this->createMock(HeaderBag::class);
        /** @var InputBag&MockObject */
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

        TestFs::register();
        $this->cacheDir = TestFs::getDevice();

        $this->listener = new ImageTransformationCache(['path' => TestFs::url('cacheDir')]);
    }

    public function tearDown(): void
    {
        TestFs::unregister();
    }

    protected function getListener(): ImageTransformationCache
    {
        return $this->listener;
    }

    /**
     * @covers ::loadFromCache
     * @covers ::getCacheDir
     * @covers ::getCacheKey
     * @covers ::getCacheFilePath
     */
    public function testChangesTheImageInstanceOnCacheHit(): void
    {
        $imageFromCache = $this->createMock(Image::class);
        $cachedData = serialize([
            'image' => $imageFromCache,
            'headers' => $this->createMock(ResponseHeaderBag::class),
        ]);

        $this->request
            ->method('getExtension')
            ->willReturn('png');

        $this->requestHeaders
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');

        $this->query
            ->method('all')
            ->with('t')
            ->willReturn(['thumbnail']);

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($imageFromCache)
            ->willReturnSelf();

        $this->event
            ->expects($this->once())
            ->method('stopPropagation');

        $dir = TestFs::url('cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/c/6');
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
    public function testRemovesCorruptCachedDataOnCacheHit(): void
    {
        $this->request
            ->method('getExtension')
            ->willReturn('png');

        $this->requestHeaders
            ->expects($this->once())
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');

        $this->query
            ->expects($this->once())
            ->method('all')
            ->with('t')
            ->willReturn(['thumbnail']);

        $dir = TestFs::url('cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/c/6');
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
    public function testAddsCorrectResponseHeaderOnCacheMiss(): void
    {
        $this->requestHeaders
            ->expects($this->once())
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('*/*');

        $this->responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-TransformationCache', 'Miss');

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers ::storeInCache
     */
    public function testDoesNotStoreNonImageModelsInTheCache(): void
    {
        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($this->createMock(Error::class));

        $this->request
            ->expects($this->never())
            ->method('getUser');

        $this->listener->storeInCache($this->event);
    }

    /**
     * @covers ::storeInCache
     * @covers ::getCacheDir
     * @covers ::getCacheKey
     * @covers ::getCacheFilePath
     */
    public function testStoresImageInCache(): void
    {
        $image = $this->createMock(Image::class);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($this->createMock(Image::class));

        $this->requestHeaders
            ->expects($this->once())
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('*/*');

        $cacheFile = TestFs::url('cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/0/5/b0571fa001b22145f82750c84c6ddda4');

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
    public function testDoesNotStoreIfCachedVersionAlreadyExists(): void
    {
        $imageFromCache = $this->createMock(Image::class);
        $cachedData = serialize([
            'image' => $imageFromCache,
            'headers' => $this->createMock(ResponseHeaderBag::class),
        ]);

        $this->request
            ->method('getExtension')
            ->willReturn('png');

        $this->requestHeaders
            ->method('get')
            ->with('Accept', '*/*')
            ->willReturn('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');

        $this->query
            ->method('all')
            ->with('t')
            ->willReturn(['thumbnail']);

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($imageFromCache)
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($this->createMock(Image::class));

        $this->event
            ->expects($this->once())
            ->method('stopPropagation');

        $dir = TestFs::url('cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/b/c/6');
        $file = 'bc6ffe312a5741a5705afe8639c08835';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, $cachedData);

        $this->listener->loadFromCache($this->event);

        $this->assertInstanceOf(ResponseHeaderBag::class, $this->response->headers);

        // Overwrite cached file
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
    public function testCanDeleteAllImageVariationsFromCache(): void
    {
        $cachedFiles = [
            TestFs::url('cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/f/30f0763c8422360d10fd84573dd58293'),
            TestFs::url('cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/e/30e0763c8422360d10fd84573dd58293'),
            TestFs::url('cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/d/30d0763c8422360d10fd84573dd58293'),
        ];

        foreach ($cachedFiles as $file) {
            mkdir(dirname($file), 0775, true);
            file_put_contents($file, 'image data');
            $this->assertTrue(is_file($file), sprintf('Expected file %s to exist', $file));
        }

        $this->listener->deleteFromCache($this->event);

        foreach ($cachedFiles as $file) {
            $this->assertFalse(is_file($file), sprintf('Did not expect file %s to exist', $file));
        }

        $this->assertFalse(is_dir(TestFs::url('cacheDir/u/s/e/user/7/b/f/7bf2e67f09de203da740a86cd37bbe8d')), 'Did not expect directory to exist');
        $this->assertTrue(is_dir(TestFs::url('cacheDir/u/s/e/user/7/b/f')), 'Expected directory to exist');
    }

    /**
     * @covers ::__construct
     */
    public function testThrowsAnExceptionWhenPathIsMissingFromTheParameters(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'The image transformation cache path is missing from the configuration',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new ImageTransformationCache([]);
    }

    /**
     * @covers ::__construct
     */
    public function testThrowsExceptionWhenCacheDirIsNotWritable(): void
    {
        $dir = TestFs::url('unwritableDir');
        mkdir($dir, 0000);

        $this->expectExceptionObject(new InvalidArgumentException(
            'Image transformation cache path is not writable by the webserver: tfs://unwritableDir',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new ImageTransformationCache(['path' => $dir]);
    }

    /**
     * @covers ::__construct
     */
    public function testDoesNotTriggerWarningIfCachePathDoesNotExistAndParentIsWritable(): void
    {
        $this->assertNotNull(
            new ImageTransformationCache(['path' => TestFs::url('cacheDir/some/dir/that/does/not/exist')]),
        );
    }
}
