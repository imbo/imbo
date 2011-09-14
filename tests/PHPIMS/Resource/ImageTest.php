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
    protected function getNewResource() {
        return new Image();
    }

    /**
     * @expectedException PHPIMS\Resource\Exception
     * @expectedException Database error: Database message
     * @expectedException 500
     */
    public function testPutWhenDatabaseThrowsAnException() {
        $this->database->expects($this->once())
                       ->method('insertImage')->with($this->publicKey, $this->imageIdentifier, $this->image)
                       ->will($this->throwException(new DatabaseException('Database message', 500)));

        $this->resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Resource\Exception
     * @expectedException Storage error: Storage message
     * @expectedException 500
     */
    public function testPutWhenStorageThrowsAnException() {
        $this->database->expects($this->once())->method('insertImage');

        $this->storage->expects($this->once())
                      ->method('store')->with($this->publicKey, $this->imageIdentifier, $this->image)
                      ->will($this->throwException(new StorageException('Storage message', 500)));

        $this->resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    public function testSuccessfulPut() {
        $this->database->expects($this->once())
                       ->method('insertImage')->with($this->publicKey, $this->imageIdentifier, $this->image);

        $this->storage->expects($this->once())
                      ->method('store')->with($this->publicKey, $this->imageIdentifier, $this->image);

        $this->response->expects($this->once())
                       ->method('setStatusCode')->with(201)
                       ->will($this->returnValue($this->response));

        $this->response->expects($this->once())
                       ->method('setBody')->with(array('imageIdentifier' => $this->imageIdentifier))
                       ->will($this->returnValue($this->response));

        $this->resource->put($this->request, $this->response, $this->database, $this->storage);
    }
}
