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

use Imbo\Resource\Metadata,
    DateTime,
    DateTimeZone;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\Resource\Metadata
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
     * @covers Imbo\Resource\Metadata::delete
     */
    public function testSupportsHttpDelete() {
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.delete');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->resource->delete($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::put
     */
    public function testSupportsHttpPut() {
        $metadata = array('foo' => 'bar');
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"foo":"bar"}'));
        $this->manager->expects($this->at(0))->method('trigger')->with('db.metadata.delete')->will($this->returnSelf());
        $this->manager->expects($this->at(1))->method('trigger')->with('db.metadata.update', array('metadata' => $metadata))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->resource->put($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::post
     */
    public function testSupportsHttpPost() {
        $metadata = array('foo' => 'bar');
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"foo":"bar"}'));
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.update', array('metadata' => $metadata));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ModelInterface'));
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
            array('key', 'id', new DateTime('@1361628772', new DateTimeZone('UTC')), '"a69f7f6c12a8fb5557afefe03ca29f91"'),
            array('key', 'id2', new DateTime('@1361628772', new DateTimeZone('UTC')), '"3e973107af60e3d6da827016b2572144"'),
            array('key2', 'id', new DateTime('@1361628772', new DateTimeZone('UTC')), '"0a7e131bb8e56db498b3a3a6f2c3c74b"'),
            array('key2', 'id2', new DateTime('@1361628772', new DateTimeZone('UTC')), '"50f886483a65680dc5ece85953d85562"'),
        );
    }

    /**
     * @dataProvider getData
     * @covers Imbo\Resource\Metadata::get
     */
    public function testSupportsHttpGet($publicKey, $imageIdentifier, $lastModified, $etag) {
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.load');
        $this->response->expects($this->once())->method('getLastModified')->will($this->returnValue($lastModified));
        $this->response->expects($this->once())->method('setEtag')->with($etag);

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));

        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing JSON data
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenValidatingMissingJsonData() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(null));
        $this->resource->validateMetadata($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid JSON data
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenValidatingInvalidJsonData() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('some string'));
        $this->resource->validateMetadata($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     */
    public function testAllowsValidJsonData() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"foo":"bar"}'));
        $this->resource->validateMetadata($this->event);
    }
}
