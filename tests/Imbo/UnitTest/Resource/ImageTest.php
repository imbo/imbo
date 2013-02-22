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
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
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

        $serverContainer = $this->getMockBuilder('Imbo\Http\ServerContainer')->disableOriginalConstructor()->getMock();
        $serverContainer->expects($this->once())->method('get')->with('REQUEST_URI')->will($this->returnValue('http://imbo/users/christer/images/id'));
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->once())->method('get')->with('Accept')->will($this->returnValue('image/*'));
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($serverContainer));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
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

        $responseHeaders->expects($this->at(0))->method('set')->with('ETag', '"e036e536dfa2eadd02c28524ee16ab72"')->will($this->returnSelf());
        $responseHeaders->expects($this->at(1))->method('set')->with('Cache-Control', 'max-age=31536000')->will($this->returnSelf());
        $responseHeaders->expects($this->at(2))->method('set')->with('X-Imbo-OriginalMimeType', 'image/png')->will($this->returnSelf());
        $responseHeaders->expects($this->at(3))->method('set')->with('X-Imbo-OriginalWidth', 200)->will($this->returnSelf());
        $responseHeaders->expects($this->at(4))->method('set')->with('X-Imbo-OriginalHeight', 100)->will($this->returnSelf());
        $responseHeaders->expects($this->at(5))->method('set')->with('X-Imbo-OriginalFileSize', 123123)->will($this->returnSelf());
        $responseHeaders->expects($this->at(6))->method('set')->with('X-Imbo-OriginalExtension', 'png')->will($this->returnSelf());

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Image::get
     */
    public function testFetchesTheImageTwiceInCaseAnEventListenerHasChangeTheInstance() {
        $serverContainer = $this->getMockBuilder('Imbo\Http\ServerContainer')->disableOriginalConstructor()->getMock();
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->request->expects($this->once())->method('getServer')->will($this->returnValue($serverContainer));
        $this->request->expects($this->once())->method('getPublicKey');
        $this->request->expects($this->once())->method('getImageIdentifier');

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setImageIdentifier')->will($this->returnSelf());
        $image->expects($this->once())->method('setPublicKey')->will($this->returnSelf());

        $modifiedImage = $this->getMock('Imbo\Model\Image');

        $this->response->expects($this->at(1))->method('getImage')->will($this->returnValue($image));
        $this->response->expects($this->at(2))->method('getImage')->will($this->returnValue($modifiedImage));
        $this->response->expects($this->once())->method('setModel')->will($this->returnValue($modifiedImage));

        $responseHeaders->expects($this->any())->method('set')->will($this->returnSelf());

        $this->resource->get($this->event);
    }
}
