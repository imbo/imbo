<?php
namespace ImboUnitTest\Resource;

use Imbo\Resource\Metadata;
use Imbo\Exception\InvalidArgumentException;

/**
 * @coversDefaultClass Imbo\Resource\Metadata
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

    protected function getNewResource() : Metadata {
        return new Metadata();
    }

    public function setUp() : void {
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->database = $this->createMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->createMock('Imbo\Storage\StorageInterface');
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->manager = $this->createMock('Imbo\EventManager\EventManager');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->manager));

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers Imbo\Resource\Metadata::delete
     */
    public function testSupportsHttpDelete() : void {
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.delete');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));

        $this->resource->delete($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::put
     */
    public function testSupportsHttpPut() : void {
        $metadata = ['foo' => 'bar'];
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"foo":"bar"}'));
        $this->manager->expects($this->at(0))->method('trigger')->with('db.metadata.delete')->will($this->returnSelf());
        $this->manager->expects($this->at(1))->method('trigger')->with('db.metadata.update', ['metadata' => $metadata])->will($this->returnSelf());
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));

        $this->resource->put($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::post
     */
    public function testSupportsHttpPost() : void {
        $metadata = ['foo' => 'bar'];
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"foo":"bar"}'));
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.update', ['metadata' => $metadata]);
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ModelInterface'));
        $this->database->expects($this->once())->method('getMetadata')->with('user', 'id')->will($this->returnValue(['foo' => 'bar']));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->resource->post($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::get
     */
    public function testSupportsHttpGet() : void {
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.load');
        $this->resource->get($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     */
    public function testThrowsExceptionWhenValidatingMissingJsonData() : void {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(null));
        $this->expectExceptionObject(new InvalidArgumentException('Missing JSON data', 400));
        $this->resource->validateMetadata($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     */
    public function testThrowsExceptionWhenValidatingInvalidJsonData() : void {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('some string'));
        $this->expectExceptionObject(new InvalidArgumentException('Invalid JSON data', 400));
        $this->resource->validateMetadata($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::validateMetadata
     */
    public function testAllowsValidJsonData() : void {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"foo":"bar"}'));
        $this->resource->validateMetadata($this->event);
    }
}
