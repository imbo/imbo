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

use Imbo\EventListener\StorageOperations,
    Imbo\Exception\StorageException;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
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
    private $response;
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
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
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
        $this->response = null;
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
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('Last-Modified', 'some date');
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('setBlob')->with('image data');
        $this->response->expects($this->once())->method('getImage')->will($this->returnValue($image));

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
        $this->response->expects($this->once())->method('setStatusCode')->with(201);
        $this->storage->expects($this->once())->method('store')->with($this->publicKey, 'checksum', 'image data');
        $this->storage->expects($this->once())->method('imageExists')->with($this->publicKey, 'checksum')->will($this->returnValue(false));

        $this->listener->insertImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\StorageOperations::insertImage
     */
    public function testCanInsertImageThatAlreadyExists() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image data'));
        $image->expects($this->once())->method('getChecksum')->will($this->returnValue('checksum'));
        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->response->expects($this->once())->method('setStatusCode')->with(200);
        $this->storage->expects($this->once())->method('store')->with($this->publicKey, 'checksum', 'image data');
        $this->storage->expects($this->once())->method('imageExists')->with($this->publicKey, 'checksum')->will($this->returnValue(true));

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
