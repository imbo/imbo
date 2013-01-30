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
    private $formatter;

    /**
     * Set up the listener
     *
     * @covers Imbo\EventListener\DatabaseOperations::setContainer
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->formatter = $this->getMock('Imbo\Helpers\DateFormatter');
        $this->container = $this->getMock('Imbo\Container');
        $this->container->expects($this->any())->method('get')->with('dateFormatter')->will($this->returnValue($this->formatter));
        $this->image = $this->getMock('Imbo\Image\Image');

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
        $this->formatter = null;
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
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue('{"key":"value"}'));
        $this->database->expects($this->once())->method('updateMetadata')->with($this->publicKey, $this->imageIdentifier, array('key' => 'value'));

        $this->listener->updateMetadata($this->event);
    }

    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadMetadata
     * @covers Imbo\EventListener\DatabaseOperations::formatDate
     */
    public function testCanLoadMetadata() {
        $date = 'Fri, 16 Mar 2012 14:05:00 GMT';
        $datetime = $this->getMock('DateTime');
        $this->formatter->expects($this->once())->method('formatDate')->with($datetime)->will($this->returnValue($date));
        $this->database->expects($this->once())->method('getMetadata')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue(array('key' => 'value')));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue($datetime));
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('Last-Modified', $date);
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

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
            ),
            array(
                'added' => $this->getMock('DateTime'),
                'updated' => $this->getMock('DateTime'),
            ),
            array(
                'added' => $this->getMock('DateTime'),
                'updated' => $this->getMock('DateTime'),
            ),
        );

        $date = 'Fri, 16 Mar 2012 14:05:00 GMT';
        $datetime = $this->getMock('DateTime');
        $this->formatter->expects($this->at(0))->method('formatDate')->with($this->isInstanceOf('DateTime'));
        $this->formatter->expects($this->at(1))->method('formatDate')->with($this->isInstanceOf('DateTime'));
        $this->formatter->expects($this->at(2))->method('formatDate')->with($this->isInstanceOf('DateTime'));
        $this->formatter->expects($this->at(3))->method('formatDate')->with($this->isInstanceOf('DateTime'));
        $this->formatter->expects($this->at(4))->method('formatDate')->with($this->isInstanceOf('DateTime'));
        $this->formatter->expects($this->at(5))->method('formatDate')->with($this->isInstanceOf('DateTime'));
        $this->formatter->expects($this->at(6))->method('formatDate')->with($datetime)->will($this->returnValue($date));

        $query = $this->getMockBuilder('Imbo\Http\ParameterContainer')->disableOriginalConstructor()->getMock();
        $query->expects($this->any(0))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(1))->method('get')->with('page')->will($this->returnValue(1));
        $query->expects($this->any(2))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(3))->method('get')->with('limit')->will($this->returnValue(5));
        $query->expects($this->any(4))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(5))->method('get')->with('metadata')->will($this->returnValue(true));
        $query->expects($this->any(6))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(7))->method('get')->with('from')->will($this->returnValue(1355156488));
        $query->expects($this->any(8))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(9))->method('get')->with('to')->will($this->returnValue(1355176488));
        $query->expects($this->any(10))->method('has')->will($this->returnValue(true));
        $query->expects($this->at(11))->method('get')->with('query')->will($this->returnValue('{"key":"value"}'));
        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($query));

        $imagesQuery = $this->getMock('Imbo\Resource\Images\Query');
        $container = $this->getMock('Imbo\Container');
        $container->expects($this->at(0))->method('get')->with('imagesQuery')->will($this->returnValue($imagesQuery));
        $container->expects($this->at(1))->method('get')->with('dateFormatter')->will($this->returnValue($this->formatter));
        $container->expects($this->at(2))->method('get')->with('dateFormatter')->will($this->returnValue($this->formatter));
        $container->expects($this->at(3))->method('get')->with('dateFormatter')->will($this->returnValue($this->formatter));
        $container->expects($this->at(4))->method('get')->with('dateFormatter')->will($this->returnValue($this->formatter));
        $container->expects($this->at(5))->method('get')->with('dateFormatter')->will($this->returnValue($this->formatter));
        $container->expects($this->at(6))->method('get')->with('dateFormatter')->will($this->returnValue($this->formatter));
        $container->expects($this->at(7))->method('get')->with('dateFormatter')->will($this->returnValue($this->formatter));

        $this->database->expects($this->once())->method('getImages')->with($this->publicKey, $imagesQuery)->will($this->returnValue($images));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey)->will($this->returnValue($datetime));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('Last-Modified', $date);
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'))->will($this->returnSelf());

        $this->listener->setContainer($container);
        $this->listener->loadImages($this->event);
    }


    /**
     * @covers Imbo\EventListener\DatabaseOperations::loadUser
     */
    public function testCanLoadUser() {
        $date = 'Fri, 16 Mar 2012 14:05:00 GMT';
        $datetime = $this->getMock('DateTime');
        $this->formatter->expects($this->once())->method('formatDate')->with($datetime)->will($this->returnValue($date));
        $this->database->expects($this->once())->method('getNumImages')->with($this->publicKey)->will($this->returnValue(123));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey)->will($this->returnValue($datetime));
        $this->response->expects($this->once())->method('setBody')->with(array(
            'publicKey' => $this->publicKey,
            'numImages' => 123,
            'lastModified' => $date,
        ));
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('Last-Modified', $date);
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->listener->loadUser($this->event);
    }
}
