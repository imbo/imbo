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
 * @covers Imbo\EventListener\ImageTransformationCache
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
    private $cn;
    private $publicKey = 'publicKey';
    private $imageIdentifier = '7bf2e67f09de203da740a86cd37bbe8d';
    private $responseHeaders;
    private $cacheDir;

    /**
     * Set up the listener
     *
     * @covers Imbo\EventListener\ImageTransformationCache::__construct
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
        $this->cn = $this->getMock('Imbo\Http\ContentNegotiation');
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getQuery')->will($this->returnValue($this->query));
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->cacheDir = vfsStream::setup($this->path);
        $this->listener = new ImageTransformationCache(vfsStream::url($this->path), $this->cn);
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->query = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->cn = null;
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
    public function testDoesNotLookInCacheIfClientDoesNotAcceptSupportedMimeTypes() {
        $mimeType = 'image/png';
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue($mimeType));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->cn->expects($this->once())->method('isAcceptable')->will($this->returnValue(false));
        $this->cn->expects($this->once())->method('bestMatch')->will($this->returnValue(false));
        $this->request->expects($this->never())->method('hasTransformations');
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array()));

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     */
    public function testDoesNotLookInCacheIfNoTransformationsIsNeededToDeliverTheImage() {
        $mimeType = 'image/png';
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue($mimeType));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->cn->expects($this->once())->method('isAcceptable')->will($this->returnValue(true));
        $this->request->expects($this->once())->method('hasTransformations');
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/png' => 1)));
        $this->request->expects($this->never())->method('getPublicKey');

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     */
    public function testLooksInCacheIfImboCanProvideAnAcceptableMimeTypeDifferentFromTheOriginal() {
        $mimeType = 'image/png';
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue($mimeType));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->cn->expects($this->once())->method('isAcceptable')->will($this->returnValue(false));
        $this->cn->expects($this->once())->method('bestMatch')->will($this->returnValue('image/jpeg'));
        $this->request->expects($this->never())->method('hasTransformations');
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/jpeg' => 1)));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->query->expects($this->once())->method('getAll')->will($this->returnValue(array()));
        $this->responseHeaders->expects($this->once())->method('set')->with('X-Imbo-TransformationCache', 'Miss');

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     */
    public function testDoesNotDoContentNegotiationWhenExtensionIsSet() {
        $mimeType = 'image/png';
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue($mimeType));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->cn->expects($this->never())->method('isAcceptable');
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/jpeg' => 1)));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->query->expects($this->once())->method('getAll')->will($this->returnValue(array()));
        $this->responseHeaders->expects($this->once())->method('set')->with('X-Imbo-TransformationCache', 'Miss');

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::loadFromCache
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheKey
     * @covers Imbo\EventListener\ImageTransformationCache::getCacheFilePath
     */
    public function testPopulatesImageOnCacheHit() {
        $mimeType = 'image/png';
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue($mimeType));
        $image->expects($this->once())->method('setBlob')->with('image data')->will($this->returnSelf());
        $image->expects($this->once())->method('setMimeType')->with('image/jpeg')->will($this->returnSelf());
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->cn->expects($this->never())->method('isAcceptable');
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/jpeg' => 1)));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->query->expects($this->once())->method('getAll')->will($this->returnValue(array()));
        $this->responseHeaders->expects($this->once())->method('set')->with('X-Imbo-TransformationCache', 'Hit');
        $this->event->expects($this->once())->method('stopPropagation')->with(true);

        $dir = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/d/c/1';
        $file = 'dc17b60a5c2182f6a424ea6df7b1d058b50e04d0e5ca9f4928892d188a813889';
        $fullPath = $dir . '/' . $file;

        mkdir($dir, 0775, true);
        file_put_contents($fullPath, 'image data');

        $this->listener->loadFromCache($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationCache::storeInCache
     */
    public function testDoesNotStoreImageInCacheIfNoTransformationHaveBeenApplied() {
        $image = $this->getMock('Imbo\Image\Image');
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
        $mimeType = 'image/png';
        $imageData = file_get_contents(FIXTURES_DIR . '/image.png');
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->will($this->returnValue(true));
        $image->expects($this->once())->method('getBlob')->will($this->returnValue($imageData));
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->query->expects($this->once())->method('getAll')->will($this->returnValue(array()));

        $cacheFile = 'vfs://cacheDir/p/u/b/publicKey/7/b/f/7bf2e67f09de203da740a86cd37bbe8d/3/0/f/30f0763c8422360d10fd84573dd582933a463e084b4f12b2b88eb1467e9eb338';

        $this->assertFalse(is_file($cacheFile));
        $this->listener->storeInCache($this->event);
        $this->assertTrue(is_file($cacheFile));
        $this->assertSame($imageData, file_get_contents($cacheFile));
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
