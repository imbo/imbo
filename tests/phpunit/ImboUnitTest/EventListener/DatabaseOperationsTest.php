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

use Imbo\EventListener\DatabaseOperations,
    Imbo\EventManager\Event,
    Imbo\Http\Response\Response,
    DateTime;

/**
 * @covers Imbo\EventListener\DatabaseOperations
 * @group unit
 * @group listeners
 * @group database
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
    private $user = 'user';
    private $imageIdentifier = 'id';
    private $image;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->accessControl = $this->getMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface');
        $this->image = $this->getMock('Imbo\Model\Image');

        $this->request->expects($this->any())->method('getUser')->will($this->returnValue($this->user));
        $this->request->expects($this->any())->method('getUsers')->will($this->returnValue([$this->user]));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getAccessControl')->will($this->returnValue($this->accessControl));

        $this->listener = new DatabaseOperations();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->database = null;
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
        $this->image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->any())->method('getImage')->will($this->returnValue($this->image));
        $this->database->expects($this->once())->method('insertImage')->with($this->user, $this->imageIdentifier, $this->image);

        $this->listener->insertImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::deleteImage
     */
    public function testCanDeleteImage() {
        $this->database->expects($this->once())->method('deleteImage')->with($this->user, $this->imageIdentifier);

        $this->listener->deleteImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadImage
     */
    public function testCanLoadImage() {
        $this->response->expects($this->any())->method('getModel')->will($this->returnValue($this->image));
        $this->database->expects($this->once())->method('load')->with($this->user, $this->imageIdentifier, $this->image);

        $this->listener->loadImage($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::deleteMetadata
     */
    public function testCanDeleteMetadata() {
        $this->database->expects($this->once())->method('deleteMetadata')->with($this->user, $this->imageIdentifier);

        $this->listener->deleteMetadata($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::updateMetadata
     */
    public function testCanUpdateMetadata() {
        $this->event->expects($this->once())->method('getArgument')->with('metadata')->will($this->returnValue(['key' => 'value']));
        $this->database->expects($this->once())->method('updateMetadata')->with($this->user, $this->imageIdentifier, ['key' => 'value']);

        $this->listener->updateMetadata($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadMetadata
     */
    public function testCanLoadMetadata() {
        $date = new DateTime();
        $this->database->expects($this->once())->method('getMetadata')->with($this->user, $this->imageIdentifier)->will($this->returnValue(['key' => 'value']));
        $this->database->expects($this->once())->method('getLastModified')->with([$this->user], $this->imageIdentifier)->will($this->returnValue($date));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Metadata'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setLastModified')->with($date);

        $this->listener->loadMetadata($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadImages
     */
    public function testCanLoadImages() {
        $images = [
            [
                'added' => new DateTime(),
                'updated' => new DateTime(),
                'size' => 123,
                'width' => 50,
                'height' => 50,
                'imageIdentifier' => 'identifier1',
                'checksum' => 'checksum1',
                'originalChecksum' => 'checksum1',
                'mime' => 'image/png',
                'extension' => 'png',
                'user' => $this->user,
                'metadata' => [],
            ],
            [
                'added' => new DateTime(),
                'updated' => new DateTime(),
                'size' => 456,
                'width' => 60,
                'height' => 60,
                'imageIdentifier' => 'identifier2',
                'checksum' => 'checksum2',
                'originalChecksum' => 'checksum2',
                'mime' => 'image/png',
                'extension' => 'png',
                'user' => $this->user,
                'metadata' => [],
            ],
            [
                'added' => new DateTime(),
                'updated' => new DateTime(),
                'size' => 789,
                'width' => 70,
                'height' => 70,
                'imageIdentifier' => 'identifier3',
                'checksum' => 'checksum3',
                'originalChecksum' => 'checksum3',
                'mime' => 'image/png',
                'extension' => 'png',
                'user' => $this->user,
                'metadata' => [],
            ],
        ];

        $date = new DateTime();

        $query = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $query->expects($this->at(0))->method('has')->with('page')->will($this->returnValue(true));
        $query->expects($this->at(1))->method('get')->with('page')->will($this->returnValue(1));
        $query->expects($this->at(2))->method('has')->with('limit')->will($this->returnValue(true));
        $query->expects($this->at(3))->method('get')->with('limit')->will($this->returnValue(5));
        $query->expects($this->at(4))->method('has')->with('metadata')->will($this->returnValue(true));
        $query->expects($this->at(5))->method('get')->with('metadata')->will($this->returnValue(true));
        $query->expects($this->at(6))->method('has')->with('from')->will($this->returnValue(true));
        $query->expects($this->at(7))->method('get')->with('from')->will($this->returnValue(1355156488));
        $query->expects($this->at(8))->method('has')->with('to')->will($this->returnValue(true));
        $query->expects($this->at(9))->method('get')->with('to')->will($this->returnValue(1355176488));
        $query->expects($this->at(10))->method('has')->with('sort')->will($this->returnValue(true));
        $query->expects($this->at(11))->method('get')->with('sort')->will($this->returnValue(['size:desc']));
        $query->expects($this->at(12))->method('has')->with('ids')->will($this->returnValue(true));
        $query->expects($this->at(13))->method('get')->with('ids')->will($this->returnValue(['identifier1', 'identifier2', 'identifier3']));
        $query->expects($this->at(14))->method('has')->with('checksums')->will($this->returnValue(true));
        $query->expects($this->at(15))->method('get')->with('checksums')->will($this->returnValue(['checksum1', 'checksum2', 'checksum3']));
        $query->expects($this->at(16))->method('has')->with('originalChecksums')->will($this->returnValue(true));
        $query->expects($this->at(17))->method('get')->with('originalChecksums')->will($this->returnValue(['checksum1', 'checksum2', 'checksum3']));
        $this->request->query = $query;

        $imagesQuery = $this->getMock('Imbo\Resource\Images\Query');
        $this->listener->setImagesQuery($imagesQuery);

        $this->database->expects($this->once())->method('getImages')->with([$this->user], $imagesQuery)->will($this->returnValue($images));
        $this->database->expects($this->once())->method('getLastModified')->with([$this->user])->will($this->returnValue($date));

        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Images'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setLastModified')->with($date);

        $this->listener->loadImages($this->event);
    }


    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadUser
     */
    public function testCanLoadUser() {
        $date = new DateTime();
        $this->database->expects($this->once())->method('getNumImages')->with($this->user)->will($this->returnValue(123));
        $this->database->expects($this->once())->method('getLastModified')->with([$this->user])->will($this->returnValue($date));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\User'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setLastModified')->with($date);

        $this->listener->loadUser($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadStats
     */
    public function testCanLoadStats() {
        $this->database->expects($this->at(0))->method('getNumImages')->will($this->returnValue(1));
        $this->database->expects($this->at(1))->method('getNumBytes')->will($this->returnValue(1));
        $this->database->expects($this->at(2))->method('getNumImages')->will($this->returnValue(2));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Stats'))->will($this->returnSelf());

        $this->listener->loadStats($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::getImagesQuery
     * @covers Imbo\EventListener\DatabaseOperations::setImagesQuery
     */
    public function testCanCreateItsOwnImagesQuery() {
        $query = $this->getMock('Imbo\Resource\Images\Query');
        $this->assertInstanceOf('Imbo\Resource\Images\Query', $this->listener->getImagesQuery());
        $this->listener->getImagesQuery();
        $this->assertSame($this->listener, $this->listener->setImagesQuery($query));
        $this->assertSame($query, $this->listener->getImagesQuery());
    }
}
