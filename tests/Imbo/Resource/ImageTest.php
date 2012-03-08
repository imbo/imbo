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
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Resource;

use Imbo\Exception\ImageException,
    Imbo\Exception\DatabaseException,
    Imbo\Exception\StorageException,
    Imbo\Exception\TransformationException;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Resource\Image
 */
class ImageTest extends ResourceTests {
    /**
     * @var Imbo\Image\ImagePreparationInterface
     */
    private $imagePreparation;

    /**
     * @var Imbo\EventManager\EventManagerInterface
     */
    private $eventManager;

    /**
     * @var Imbo\Image\ImageInterface
     */
    private $image;

    protected function getNewResource() {
        $this->eventManager = $this->getMock('Imbo\EventManager\EventManagerInterface');
        $image = new Image($this->image, $this->imagePreparation);
        $image->setEventManager($this->eventManager);

        return $image;
    }

    public function setUp() {
        $this->image = $this->getMock('Imbo\Image\ImageInterface');
        $this->imagePreparation = $this->getMock('Imbo\Image\ImagePreparationInterface');

        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();

        $this->image = null;
        $this->imagePreparation = null;
    }

    /**
     * @covers Imbo\Resource\Image::put
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 400
     */
    public function testPutWhenImagePreparationThrowsException() {
        $this->imagePreparation->expects($this->once())
                               ->method('prepareImage')
                               ->with($this->request, $this->image)
                               ->will($this->throwException(new ImageException('message', 400)));

        $this->getNewResource()->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::put
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 500
     */
    public function testPutWhenDatabaseThrowsException() {
        $this->request->expects($this->once())
                      ->method('getPublicKey')
                      ->will($this->returnValue($this->publicKey));

        $this->request->expects($this->once())
                      ->method('getRealImageIdentifier')
                      ->will($this->returnValue($this->imageIdentifier));

        $this->database->expects($this->once())
                       ->method('insertImage')
                       ->with($this->publicKey, $this->imageIdentifier, $this->image)
                       ->will($this->throwException(new DatabaseException('message', 500)));

        $this->getNewResource()->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::put
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 500
     */
    public function testPutWhenStorageThrowsException() {
        $this->request->expects($this->once())
                      ->method('getPublicKey')
                      ->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())
                      ->method('getRealImageIdentifier')
                      ->will($this->returnValue($this->imageIdentifier));

        $this->database->expects($this->once())
                       ->method('insertImage')
                       ->with($this->publicKey, $this->imageIdentifier, $this->image);

        $this->database->expects($this->once())
                       ->method('deleteImage')
                       ->with($this->publicKey, $this->imageIdentifier);

        $this->storage->expects($this->once())
                      ->method('store')
                      ->with($this->publicKey, $this->imageIdentifier, $this->image)
                      ->will($this->throwException(new StorageException('message', 500)));

        $this->getNewResource()->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::put
     */
    public function testSuccessfulPut() {
        $writer = $this->getMock('Imbo\Http\Response\ResponseWriter');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $this->request->expects($this->once())
                      ->method('getPublicKey')
                      ->will($this->returnValue($this->publicKey));

        $this->request->expects($this->once())
                      ->method('getRealImageIdentifier')
                      ->will($this->returnValue($this->imageIdentifier));

        $this->database->expects($this->once())
                       ->method('insertImage')
                       ->with($this->publicKey, $this->imageIdentifier, $this->image);

        $this->storage->expects($this->once())
                      ->method('store')
                      ->with($this->publicKey, $this->imageIdentifier, $this->image);

        $this->response->expects($this->once())->method('setStatusCode')->with(201)->will($this->returnValue($this->response));

        $resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::delete
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 500
     */
    public function testDeleteWhenDatabaseThrowsAnException() {
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->database->expects($this->once())
                       ->method('deleteImage')
                       ->with($this->publicKey, $this->imageIdentifier)
                       ->will($this->throwException(new DatabaseException('message', 500)));

        $this->getNewResource()->delete($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::delete
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 500
     */
    public function testDeleteWhenStorageThrowsAnException() {
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->database->expects($this->once())
                       ->method('deleteImage')
                       ->with($this->publicKey, $this->imageIdentifier);

        $this->storage->expects($this->once())
                      ->method('delete')
                      ->with($this->publicKey, $this->imageIdentifier)
                      ->will($this->throwException(new StorageException('message', 500)));

        $this->getNewResource()->delete($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::delete
     */
    public function testSuccessfulDelete() {
        $writer = $this->getMock('Imbo\Http\Response\ResponseWriter');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->database->expects($this->once())
                       ->method('deleteImage')
                       ->with($this->publicKey, $this->imageIdentifier);

        $this->storage->expects($this->once())
                      ->method('delete')
                      ->with($this->publicKey, $this->imageIdentifier);

        $resource->delete($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::get
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 500
     */
    public function testGetWhenDatabaseThrowsException() {
        $this->database->expects($this->once())
                       ->method('load')
                       ->will($this->throwException(new DatabaseException('message', 500)));

        $this->getNewResource()->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::get
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 500
     */
    public function testGetWhenStorageThrowsException() {
        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($this->getMock('Imbo\Http\ServerContainerInterface')));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($this->getMock('Imbo\Http\HeaderContainer')));
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($this->getMock('Imbo\Http\HeaderContainer')));

        $this->database->expects($this->once())
                       ->method('load');

        $this->storage->expects($this->once())
                      ->method('load')
                      ->will($this->throwException(new StorageException('message', 500)));

        $this->getNewResource()->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::get
     */
    public function testGetWhenResponseIsNotModified() {
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($this->getMock('Imbo\Http\HeaderContainer')));

        // The request URI for some image
        $requestUri = '/users/' . $this->publicKey . '/images/' . $this->imageIdentifier . '.png';

        // Timestamp used for the Last-Modified header
        $lastModified = date('D, d M Y H:i:s') . ' GMT';

        // Generate ETag as it appears in the headers
        $etag = '"' . md5($this->publicKey . $this->imageIdentifier . $requestUri) . '"';

        $this->storage->expects($this->once())->method('getLastModified')->will($this->returnValue($lastModified));

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $serverContainer = $this->getMock('Imbo\Http\ServerContainerInterface');
        $serverContainer->expects($this->once())->method('get')->with('REQUEST_URI')->will($this->returnValue($requestUri));

        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($serverContainer));

        $requestHeaders->expects($this->any())->method('get')->will($this->returnCallback(function($arg) use($etag, $lastModified) {
            if ($arg == 'if-modified-since') {
                return $lastModified;
            }

            return $etag;
        }));

        $this->response->expects($this->once())->method('setNotModified');

        $this->getNewResource()->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Image::get
     */
    public function testGetWithImageConversion() {
        if (!class_exists('Imagine\Imagick\Imagine')) {
            $this->markTestSkipped('Imagine must be available to run this test');
        }

        $resourcePart = $this->imageIdentifier . '.jpg';
        $requestUri = '/users/' . $this->publicKey . '/images/' . $resourcePart;

        $serverContainer = $this->getMock('Imbo\Http\ServerContainerInterface');
        $serverContainer->expects($this->once())->method('get')->with('REQUEST_URI')->will($this->returnValue($requestUri));

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnValue($responseHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($serverContainer));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->request->expects($this->once())->method('getPath')->will($this->returnValue($requestUri));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue($this->getMock('Imbo\Image\TransformationChain')));

        $this->image->expects($this->any())->method('getBlob')->will($this->returnValue(file_get_contents(__DIR__ . '/../_files/image.png')));

        $this->response->expects($this->once())->method('setBody')->with($this->isType('string'));

        $this->getNewResource()->get($this->request, $this->response, $this->database, $this->storage);
    }
}
