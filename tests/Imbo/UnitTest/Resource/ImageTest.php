<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Resource;

use Imbo\Resource\Image;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
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
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->manager = $this->getMock('Imbo\EventManager\EventManager');
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
     * @covers Imbo\Resource\Image::put
     */
    public function testSupportsHttpPut() {
        $this->manager->expects($this->at(0))->method('trigger')->with('db.image.insert');
        $this->manager->expects($this->at(1))->method('trigger')->with('storage.image.insert');
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getChecksum')->will($this->returnValue('id'));
        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));

        $this->resource->put($this->event);
    }

    /**
     * @covers Imbo\Resource\Image::delete
     */
    public function testSupportsHttpDelete() {
        $this->manager->expects($this->at(0))->method('trigger')->with('db.image.delete');
        $this->manager->expects($this->at(1))->method('trigger')->with('storage.image.delete');
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));

        $this->resource->delete($this->event);
    }

    /**
     * @covers Imbo\Resource\Image::get
     */
    public function testSupportsHttpGet() {
        $publicKey = 'christer';
        $imageIdentifier = 'imageIdentifier';

        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('Accept')->will($this->returnValue('image/*'));

        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');

        $this->request->headers = $requestHeaders;

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('getRequestUri')->will($this->returnValue('/users/christer/images/id'));

        $this->response->headers = $responseHeaders;

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setPublicKey')->with($publicKey)->will($this->returnSelf());
        $image->expects($this->once())->method('setImageIdentifier')->with($imageIdentifier)->will($this->returnSelf());

        $image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/png'));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(200));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(100));
        $image->expects($this->once())->method('getFilesize')->will($this->returnValue(123123));
        $image->expects($this->once())->method('getExtension')->will($this->returnValue('png'));

        $this->response->expects($this->exactly(2))->method('getImage')->will($this->returnValue($image));
        $this->response->expects($this->once())->method('setModel')->will($this->returnValue($image));

        $this->manager->expects($this->at(0))->method('trigger')->with('db.image.load');
        $this->manager->expects($this->at(1))->method('trigger')->with('storage.image.load');

        $this->response->expects($this->once())->method('setEtag')->with('"0d3c8690e9ad4b2d3c22520b4c0f4321"')->will($this->returnSelf());
        $this->response->expects($this->once())->method('setMaxAge')->with(31536000)->will($this->returnSelf());

        $responseHeaders->expects($this->once())->method('add')->with(array(
            'X-Imbo-OriginalMimeType' => 'image/png',
            'X-Imbo-OriginalWidth' => 200,
            'X-Imbo-OriginalHeight' => 100,
            'X-Imbo-OriginalFileSize' => 123123,
            'X-Imbo-OriginalExtension' => 'png',
        ));

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Image::get
     */
    public function testFetchesTheImageTwiceInCaseAnEventListenerHasChangeTheInstance() {
        $serverBag = $this->getMock('Symfony\Component\HttpFoundation\ServerBag');
        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\ServerBag');
        $responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ServerBag');

        $this->response->headers = $responseHeaders;
        $this->request->headers = $requestHeaders;
        $this->request->server = $serverBag;
        $this->request->expects($this->once())->method('getPublicKey');
        $this->request->expects($this->once())->method('getImageIdentifier');

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setImageIdentifier')->will($this->returnSelf());
        $image->expects($this->once())->method('setPublicKey')->will($this->returnSelf());

        $modifiedImage = $this->getMock('Imbo\Model\Image');

        $this->response->expects($this->at(0))->method('getImage')->will($this->returnValue($image));
        $this->response->expects($this->at(1))->method('setEtag')->with('"d41d8cd98f00b204e9800998ecf8427e"')->will($this->returnSelf());
        $this->response->expects($this->at(2))->method('setMaxAge')->with(31536000);
        $this->response->expects($this->at(3))->method('getImage')->will($this->returnValue($modifiedImage));
        $this->response->expects($this->at(4))->method('setModel')->with($modifiedImage);

        $responseHeaders->expects($this->any())->method('add')->will($this->returnSelf());

        $this->resource->get($this->event);
    }
}
