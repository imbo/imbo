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

namespace Imbo\UnitTest\Resource;

use Imbo\Resource\Image,
    Imbo\Exception\ImageException,
    Imbo\Exception\DatabaseException,
    Imbo\Exception\StorageException,
    Imbo\Exception\TransformationException;

/**
 * @package TestSuite\UnitTests
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
     * @var Imbo\Http\ContentNegotiation
     */
    private $contentNegotiation;

    /**
     * @var Imbo\EventManager\EventManagerInterface
     */
    private $eventManager;

    /**
     * @var Imbo\Image\ImageInterface
     */
    private $image;

    /**
     * {@inheritdoc}
     */
    protected function getNewResource() {
        $this->eventManager = $this->getMock('Imbo\EventManager\EventManagerInterface');
        $image = new Image($this->image, $this->imagePreparation, $this->contentNegotiation);
        $image->setEventManager($this->eventManager);

        return $image;
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->image = $this->getMock('Imbo\Image\ImageInterface');
        $this->imagePreparation = $this->getMock('Imbo\Image\ImagePreparationInterface');
        $this->contentNegotiation = $this->getMock('Imbo\Http\ContentNegotiation');

        parent::setUp();
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        parent::tearDown();

        $this->image = null;
        $this->imagePreparation = null;
        $this->contentNegotiation = null;
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

        $this->getNewResource()->put($this->container);
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

        $this->getNewResource()->put($this->container);
    }

    /**
     * @covers Imbo\Resource\Image::put
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 500
     */
    public function testPutWhenStorageThrowsException() {
        $imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $this->image->expects($this->once())
                    ->method('getBlob')
                    ->will($this->returnValue($imageData));

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
                      ->with($this->publicKey, $this->imageIdentifier, $imageData)
                      ->will($this->throwException(new StorageException('message', 500)));

        $this->getNewResource()->put($this->container);
    }

    /**
     * @covers Imbo\Resource\Image::put
     */
    public function testSuccessfulPut() {
        $imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $this->image->expects($this->once())->method('getBlob')->will($this->returnValue($imageData));

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getRealImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->database->expects($this->once())->method('insertImage')->with($this->publicKey, $this->imageIdentifier, $this->image);

        $this->storage->expects($this->once())->method('store')->with($this->publicKey, $this->imageIdentifier, $imageData);

        $this->response->expects($this->once())->method('setStatusCode')->with(201)->will($this->returnValue($this->response));
        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));

        $this->getNewResource()->put($this->container);
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

        $this->getNewResource()->delete($this->container);
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

        $this->getNewResource()->delete($this->container);
    }

    /**
     * @covers Imbo\Resource\Image::delete
     */
    public function testSuccessfulDelete() {
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));

        $this->database->expects($this->once())->method('deleteImage')->with($this->publicKey, $this->imageIdentifier);

        $this->storage->expects($this->once())->method('delete')->with($this->publicKey, $this->imageIdentifier);

        $this->getNewResource()->delete($this->container);
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

        $this->getNewResource()->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Image::get
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage message
     * @expectedExceptionCode 500
     */
    public function testGetWhenStorageThrowsException() {
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get');

        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($this->getMock('Imbo\Http\ServerContainerInterface')));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($this->getMock('Imbo\Http\HeaderContainer')));

        $this->database->expects($this->once())
                       ->method('load');

        $this->storage->expects($this->once())->method('getLastModified')->will($this->returnValue($this->getMock('DateTime')));
        $this->storage->expects($this->once())
                      ->method('getImage')
                      ->will($this->throwException(new StorageException('message', 500)));

        $this->getNewResource()->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Image::get
     */
    public function testGetWithImageConversion() {
        $serverContainer = $this->getMock('Imbo\Http\ServerContainerInterface');
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnValue($responseHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($serverContainer));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue('jpg'));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array()));

        $this->storage->expects($this->once())->method('getLastModified')->will($this->returnValue($this->getMock('DateTime')));

        $this->image->expects($this->any())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $this->image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/png'));

        $this->response->expects($this->once())->method('setBody')->with($this->isType('string'));

        $convert = $this->getMockBuilder('Imbo\Image\Transformation\Convert')->disableOriginalConstructor()->getMock();

        $this->container->config = array(
            'transformations' => array(
                'convert' => function ($params) use ($convert) {
                    return $convert;
                },
            ),
        );

        $this->getNewResource()->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Image::get
     */
    public function testGet() {
        $imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $serverContainer = $this->getMock('Imbo\Http\ServerContainerInterface');
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnValue($responseHeaders));

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get');

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($serverContainer));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array()));
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('*/*' => 1)));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setBody')->with($imageData);

        $this->storage->expects($this->once())->method('getImage')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue($imageData));
        $this->storage->expects($this->once())->method('getLastModified')->will($this->returnValue($this->getMock('DateTime')));
        $this->image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/png'));
        $this->image->expects($this->once())->method('setBlob')->with($imageData);
        $this->image->expects($this->once())->method('getBlob')->will($this->returnValue($imageData));
        $this->contentNegotiation->expects($this->once())->method('bestMatch')->will($this->returnValue('image/png'));

        $this->getNewResource()->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Image::get
     * @expectedException Imbo\Exception\ResourceException
     * @expectedExceptionCode 406
     */
    public function testGetWhenUserAgentDoesNotAcceptImage() {
        $imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $serverContainer = $this->getMock('Imbo\Http\ServerContainerInterface');
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnValue($responseHeaders));

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get');

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($serverContainer));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array()));
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/jpeg' => 1)));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->storage->expects($this->once())->method('getImage')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue($imageData));
        $this->storage->expects($this->once())->method('getLastModified')->will($this->returnValue($this->getMock('DateTime')));

        $this->getNewResource()->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Image::get
     */
    public function testGetWhenUserAgentDoesNotAcceptOriginalMimeType() {
        $serverContainer = $this->getMock('Imbo\Http\ServerContainerInterface');
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnValue($responseHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($serverContainer));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array()));
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/jpeg' => 1)));

        $this->storage->expects($this->once())->method('getLastModified')->will($this->returnValue($this->getMock('DateTime')));

        $this->image->expects($this->any())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $this->image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/png'));

        $this->response->expects($this->once())->method('setBody')->with($this->isType('string'));

        $this->contentNegotiation->expects($this->once())->method('bestMatch')->with($this->isType('array'), array('image/jpeg' => 1))->will($this->returnValue('image/jpeg'));

        $convert = $this->getMockBuilder('Imbo\Image\Transformation\Convert')->disableOriginalConstructor()->getMock();

        $this->container->config = array(
            'transformations' => array(
                'convert' => function ($params) use ($convert) {
                    return $convert;
                },
            ),
        );

        $this->getNewResource()->get($this->container);
    }
}
