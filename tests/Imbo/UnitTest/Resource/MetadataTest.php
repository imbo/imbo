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

use Imbo\Resource\Metadata;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class MetadataTest extends ResourceTests {
    /**
     * @var Metadata
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
        return new Metadata();
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
     * @covers Imbo\Resource\Metadata::delete
     */
    public function testSupportsHttpDelete() {
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.delete');
        $this->response->expects($this->once())->method('setBody')->with(array('imageIdentifier' => 'id'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->resource->delete($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::put
     */
    public function testSupportsHttpPut() {
        $this->manager->expects($this->at(0))->method('trigger')->with('db.metadata.delete')->will($this->returnSelf());
        $this->manager->expects($this->at(1))->method('trigger')->with('db.metadata.update')->will($this->returnSelf());
        $this->response->expects($this->once())->method('setBody')->with(array('imageIdentifier' => 'id'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->resource->put($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::post
     */
    public function testSupportsHttpPost() {
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.update');
        $this->response->expects($this->once())->method('setBody')->with(array('imageIdentifier' => 'id'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->resource->post($this->event);
    }

    /**
     * Return data used in the GET test
     *
     * @return array[]
     */
    public function getData() {
        return array(
            array('key', 'id', 'some date', '"d319355bcb6f1336d99ac49506fe7338"'),
            array('key', 'id2', 'some date', '"b75f50d8f98f0cb173405188b103b69d"'),
            array('key2', 'id', 'some date', '"58bf743310061549ac6919e34fd89de1"'),
            array('key2', 'id2', 'some date', '"6c3c80af196bc8d62cc4d7b3bbcc9827"'),
        );
    }

    /**
     * @dataProvider getData
     * @covers Imbo\Resource\Metadata::get
     */
    public function testSupportsHttpGet($publicKey, $imageIdentifier, $lastModified, $etag) {
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.load');
        $this->response->expects($this->once())->method('getLastModified')->will($this->returnValue($lastModified));

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->at(0))->method('set')->with('ETag', $etag);
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing JSON data
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenValidatingMissingJsonData() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(null));
        $this->resource->validateMetadata($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid JSON data
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenValidatingInvalidJsonData() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue('some string'));
        $this->resource->validateMetadata($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     */
    public function testAllowsValidJsonData() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue('{"foo":"bar"}'));
        $this->resource->validateMetadata($this->event);
    }
}
