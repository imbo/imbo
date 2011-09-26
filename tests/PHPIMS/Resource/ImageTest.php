<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource;

use PHPIMS\Image\Exception as ImageException;
use PHPIMS\Database\Exception as DatabaseException;
use PHPIMS\Storage\Exception as StorageException;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ImageTest extends ResourceTests {
    private $imageIdentification;
    private $imagePreparation;
    private $image;

    protected function getNewResource() {
        $this->image = $this->getMock('PHPIMS\Image\ImageInterface');
        $this->imageIdentification = $this->getMock('PHPIMS\Image\ImageIdentificationInterface');
        $this->imagePreparation = $this->getMock('PHPIMS\Image\ImagePreparationInterface');

        return new Image($this->image, $this->imageIdentification, $this->imagePreparation);
    }

    public function tearDown() {
        parent::tearDown();

        $this->image = null;
        $this->imageIdentification = null;
        $this->imagePreparation = null;
    }

    /**
     * @expectedException PHPIMS\Resource\Exception
     * @expectedExceptionMessage message
     * @expectedExceptionCode 400
     */
    public function testPutWhenImagePreparationThrowsException() {
        $resource = $this->getNewResource();

        $this->imagePreparation->expects($this->once())
                               ->method('prepareImage')
                               ->with($this->request, $this->image)
                               ->will($this->throwException(new ImageException('message', 400)));

        $resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Resource\Exception
     * @expectedExceptionMessage message
     * @expectedExceptionCode 400
     */
    public function testPutWhenImageIdentificationThrowsException() {
        $resource = $this->getNewResource();

        $this->imagePreparation->expects($this->once())
                               ->method('prepareImage')
                               ->with($this->request, $this->image);

        $this->imageIdentification->expects($this->once())
                                  ->method('identifyImage')
                                  ->with($this->image)
                                  ->will($this->throwException(new ImageException('message', 400)));

        $resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Resource\Exception
     * @expectedExceptionMessage Database error: message
     * @expectedExceptionCode 500
     */
    public function testPutWhenDatabaseThrowsException() {
        $publicKey = md5(microtime());
        $imageIdentifier = md5(microtime()) . '.png';
        $resource = $this->getNewResource();

        $this->request->expects($this->once())
                      ->method('getPublicKey')
                      ->will($this->returnValue($publicKey));
        $this->request->expects($this->once())
                      ->method('getImageIdentifier')
                      ->will($this->returnValue($imageIdentifier));

        $this->image->expects($this->once())
                    ->method('getExtension')
                    ->will($this->returnValue('png'));

        $this->database->expects($this->once())
                       ->method('insertImage')
                       ->with($publicKey, $imageIdentifier, $this->image)
                       ->will($this->throwException(new DatabaseException('message', 500)));

        $resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Resource\Exception
     * @expectedExceptionMessage Storage error: message
     * @expectedExceptionCode 500
     */
    public function testPutWhenStorageThrowsException() {
        $publicKey = md5(microtime());
        $imageIdentifier = md5(microtime()) . '.png';
        $resource = $this->getNewResource();

        $this->request->expects($this->once())
                      ->method('getPublicKey')
                      ->will($this->returnValue($publicKey));
        $this->request->expects($this->once())
                      ->method('getImageIdentifier')
                      ->will($this->returnValue($imageIdentifier));

        $this->image->expects($this->once())
                    ->method('getExtension')
                    ->will($this->returnValue('png'));

        $this->database->expects($this->once())
                       ->method('insertImage')
                       ->with($publicKey, $imageIdentifier, $this->image);

        $this->storage->expects($this->once())
                      ->method('store')
                      ->with($publicKey, $imageIdentifier, $this->image)
                      ->will($this->throwException(new StorageException('message', 500)));

        $resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    public function testSuccessfulPut() {
        $publicKey = md5(microtime());
        $imageIdentifier = md5(microtime()) . '.png';
        $resource = $this->getNewResource();

        $this->request->expects($this->once())
                      ->method('getPublicKey')
                      ->will($this->returnValue($publicKey));
        $this->request->expects($this->once())
                      ->method('getImageIdentifier')
                      ->will($this->returnValue($imageIdentifier));

        $this->image->expects($this->once())
                    ->method('getExtension')
                    ->will($this->returnValue('png'));

        $this->database->expects($this->once())
                       ->method('insertImage')
                       ->with($publicKey, $imageIdentifier, $this->image);

        $this->storage->expects($this->once())
                      ->method('store')
                      ->with($publicKey, $imageIdentifier, $this->image);

        $this->response->expects($this->once())->method('setStatusCode')->with(201)->will($this->returnValue($this->response));

        $resource->put($this->request, $this->response, $this->database, $this->storage);
    }
}
