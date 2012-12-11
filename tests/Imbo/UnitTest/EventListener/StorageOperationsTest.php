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

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\StorageOperations,
    Imbo\Exception\StorageException;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\StorageOperations
 */
class StorageOperationsTest extends ListenerTests {
    /**
     * @var StorageOperations
     */
    private $listener;

    private $container;
    private $event;
    private $request;
    private $publicKey = 'key';
    private $imageIdentifier = 'id';
    private $storage;

    /**
     * Set up the listener
     *
     * @covers Imbo\EventListener\StorageOperations::setContainer
     */
    public function setUp() {
        $this->container = $this->getMock('Imbo\Container');
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));

        $this->listener = new StorageOperations();
        $this->listener->setContainer($this->container);
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->container = null;
        $this->listener = null;
        $this->request = null;
        $this->storage = null;
        $this->event = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\StorageOperations::deleteImage
     */
    public function testCanDeleteanImage() {
        $this->storage->expects($this->once())->method('delete')->with($this->publicKey, $this->imageIdentifier);
        $this->listener->deleteImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\StorageOperations::loadImage
     */
    public function testCanLoadImage() {
        $datetime = $this->getMock('DateTime');
        $this->storage->expects($this->once())->method('getImage')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue('image data'));
        $this->storage->expects($this->once())->method('getLastModified')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue($datetime));
        $formatter = $this->getMock('Imbo\Helpers\DateFormatter');
        $formatter->expects($this->once())->method('formatDate')->with($datetime)->will($this->returnValue('some date'));
        $this->container->expects($this->once())->method('get')->with('dateFormatter')->will($this->returnValue($formatter));
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('Last-Modified', 'some date');
        $response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('setBlob')->with('image data');
        $response->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($response));

        $this->listener->loadImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\StorageOperations::insertImage
     */
    public function testCanInsertImage() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image data'));
        $image->expects($this->once())->method('getChecksum')->will($this->returnValue('checksum'));
        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->storage->expects($this->once())->method('store')->with($this->publicKey, 'checksum', 'image data');

        $this->listener->insertImage($this->event);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage Could not store image
     * @expectedExceptionCode 500
     * @covers Imbo\EventListener\StorageOperations::insertImage
     */
    public function testWillDeleteImageFromDatabaseAndThrowExceptionWhenStoringFails() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image data'));
        $image->expects($this->once())->method('getChecksum')->will($this->returnValue('checksum'));
        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->storage->expects($this->once())->method('store')->with($this->publicKey, 'checksum', 'image data')->will($this->throwException(
            new StorageException('Could not store image', 500)
        ));
        $database = $this->getMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('deleteImage')->with($this->publicKey, 'checksum');
        $this->event->expects($this->once())->method('getDatabase')->will($this->returnValue($database));

        $this->listener->insertImage($this->event);
    }
}
