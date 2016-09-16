<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Resource;

use Imbo\Resource\Image;

/**
 * @covers Imbo\Resource\Image
 * @group unit
 * @group resources
 */
class ImageTest extends ResourceTests {
    /**
     * @var Image
     */
    private $resource;

    private $request;
    private $response;
    private $database;
    private $storage;
    private $manager;
    private $event;

    /**
     * {@inheritdoc}
     */
    protected function getNewResource() {
        return new Image();
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->manager = $this->getMockBuilder('Imbo\EventManager\EventManager')->disableOriginalConstructor()->getMock();
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->manager));

        $this->resource = $this->getNewResource();
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
        $this->response = null;
        $this->database = null;
        $this->storage = null;
        $this->event = null;
        $this->manager = null;
    }

    /**
     * @covers Imbo\Resource\Image::deleteImage
     */
    public function testSupportsHttpDelete() {
        $this->manager->expects($this->at(0))->method('trigger')->with('db.image.delete');
        $this->manager->expects($this->at(1))->method('trigger')->with('storage.image.delete');
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));

        $this->resource->deleteImage($this->event);
    }

    /**
     * @covers Imbo\Resource\Image::getImage
     */
    public function testSupportsHttpGet() {
        $user = 'christer';
        $imageIdentifier = 'imageIdentifier';

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');

        $this->request->expects($this->once())->method('getUser')->will($this->returnValue($user));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));

        $this->response->headers = $responseHeaders;

        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\Image'));

        $this->manager->expects($this->at(0))->method('trigger')->with('db.image.load');
        $this->manager->expects($this->at(1))->method('trigger')->with('storage.image.load');

        $this->response->expects($this->once())->method('setMaxAge')->with(31536000)->will($this->returnSelf());

        $responseHeaders->expects($this->once())->method('add')->with($this->callback(function($headers) {
            return array_key_exists('X-Imbo-OriginalMimeType', $headers) &&
                   array_key_exists('X-Imbo-OriginalWidth', $headers) &&
                   array_key_exists('X-Imbo-OriginalHeight', $headers) &&
                   array_key_exists('X-Imbo-OriginalFileSize', $headers) &&
                   array_key_exists('X-Imbo-OriginalExtension', $headers);
        }));

        $this->resource->getImage($this->event);
    }
}
