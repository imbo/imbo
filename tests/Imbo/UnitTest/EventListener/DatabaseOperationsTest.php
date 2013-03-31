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

use Imbo\EventListener\DatabaseOperations;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class DatabaseOperationsTest extends ListenerTests {
    /**
     * @var DatabaseOperations
     */
    private $listener;

    private $event;
    private $request;
    private $response;
    private $database;
    private $container;
    private $publicKey = 'key';
    private $imageIdentifier = 'id';
    private $image;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->container = $this->getMock('Imbo\Container');
        $this->image = $this->getMock('Imbo\Model\Image');

        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));

        $this->listener = new DatabaseOperations();
        $this->listener->setContainer($this->container);
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->database = null;
        $this->container = null;
        $this->image = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::insertImage
     */
    public function testCanInsertImage() {
        $this->image->expects($this->once())->method('getChecksum')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->any())->method('getImage')->will($this->returnValue($this->image));
        $this->database->expects($this->once())->method('insertImage')->with($this->publicKey, $this->imageIdentifier, $this->image);

        $this->listener->insertImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::deleteImage
     */
    public function testCanDeleteImage() {
        $this->database->expects($this->once())->method('deleteImage')->with($this->publicKey, $this->imageIdentifier);

        $this->listener->deleteImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadImage
     */
    public function testCanLoadImage() {
        $this->response->expects($this->any())->method('getImage')->will($this->returnValue($this->image));
        $this->database->expects($this->once())->method('load')->with($this->publicKey, $this->imageIdentifier, $this->image);

        $this->listener->loadImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::deleteMetadata
     */
    public function testCanDeleteMetadata() {
        $this->database->expects($this->once())->method('deleteMetadata')->with($this->publicKey, $this->imageIdentifier);

        $this->listener->deleteMetadata($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::updateMetadata
     */
    public function testCanUpdateMetadata() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"key":"value"}'));
        $this->database->expects($this->once())->method('updateMetadata')->with($this->publicKey, $this->imageIdentifier, array('key' => 'value'));

        $this->listener->updateMetadata($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadMetadata
     */
    public function testCanLoadMetadata() {
        $date = $this->getMock('DateTime');
        $this->database->expects($this->once())->method('getMetadata')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue(array('key' => 'value')));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue($date));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Metadata'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setLastModified')->with($date);

        $this->listener->loadMetadata($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadImages
     */
    public function testCanLoadImages() {
        $images = array(
            array(
                'added' => $this->getMock('DateTime'),
                'updated' => $this->getMock('DateTime'),
                'size' => 123,
                'width' => 50,
                'height' => 50,
                'imageIdentifier' => 'identifier1',
                'checksum' => 'checksum1',
                'mime' => 'image/png',
                'extension' => 'png',
                'metadata' => array(),
            ),
            array(
                'added' => $this->getMock('DateTime'),
                'updated' => $this->getMock('DateTime'),
                'size' => 456,
                'width' => 60,
                'height' => 60,
                'imageIdentifier' => 'identifier2',
                'checksum' => 'checksum2',
                'mime' => 'image/png',
                'extension' => 'png',
                'metadata' => array(),
            ),
            array(
                'added' => $this->getMock('DateTime'),
                'updated' => $this->getMock('DateTime'),
                'size' => 789,
                'width' => 70,
                'height' => 70,
                'imageIdentifier' => 'identifier3',
                'checksum' => 'checksum3',
                'mime' => 'image/png',
                'extension' => 'png',
                'metadata' => array(),
            ),
        );

        $date = $this->getMock('DateTime');

        $query = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $query->expects($this->at(0))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(1))->method('get')->with('page')->will($this->returnValue(1));
        $query->expects($this->at(2))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(3))->method('get')->with('limit')->will($this->returnValue(5));
        $query->expects($this->at(4))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(5))->method('get')->with('metadata')->will($this->returnValue(true));
        $query->expects($this->at(6))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(7))->method('get')->with('from')->will($this->returnValue(1355156488));
        $query->expects($this->at(8))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(9))->method('get')->with('to')->will($this->returnValue(1355176488));
        $query->expects($this->at(10))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(11))->method('get')->with('query')->will($this->returnValue('{"key":"value"}'));
        $this->request->query = $query;

        $imagesQuery = $this->getMock('Imbo\Resource\Images\Query');
        $container = $this->getMock('Imbo\Container');
        $container->expects($this->at(0))->method('get')->with('imagesQuery')->will($this->returnValue($imagesQuery));

        $this->database->expects($this->once())->method('getImages')->with($this->publicKey, $imagesQuery)->will($this->returnValue($images));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey)->will($this->returnValue($date));

        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Images'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setLastModified')->with($date);

        $this->listener->setContainer($container);
        $this->listener->loadImages($this->event);
    }


    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadUser
     */
    public function testCanLoadUser() {
        $date = $this->getMock('DateTime');
        $this->database->expects($this->once())->method('getNumImages')->with($this->publicKey)->will($this->returnValue(123));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey)->will($this->returnValue($date));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\User'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setLastModified')->with($date);

        $this->listener->loadUser($this->event);
    }
}
