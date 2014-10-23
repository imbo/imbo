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

use Imbo\Resource\Metadata,
    Imbo\Exception\InvalidArgumentException,
    DateTime,
    DateTimeZone;

/**
 * @covers Imbo\Resource\Metadata
 * @group unit
 * @group resources
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
        $this->database->expects($this->once())->method('getMetadata')->with('key', 'id')->will($this->returnValue(array('foo' => 'bar')));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue('key'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->resource->post($this->event);
    }

    /**
     * @covers Imbo\Resource\Metadata::get
     */
    public function testSupportsHttpGet() {
        $this->manager->expects($this->once())->method('trigger')->with('db.metadata.load');
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

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getMetadataKeys() {
        return array(
            'valid data' => array(
                'metadata' => '{"key": "value"}',
                'fail'     => false,
            ),
            'key with dot' => array(
                'metadata'   => '{"foo.bar": "value"}',
                'fail'       => true,
                'invalidKey' => 'foo.bar',
            ),
            'key with double colon' => array(
                'metadata'   => '{"foo::bar": "value"}',
                'fail'       => true,
                'invalidKey' => 'foo::bar',
            ),
            'key that starts with a dollar sign' => array(
                'metadata'   => '{"$foo": "value"}',
                'fail'       => true,
                'invalidKey' => '$foo',
            ),
            'key that contains a dollar sign' => array(
                'metadata' => '{"foo$bar": "value"}',
                'fail'     => false,
            ),
        );
    }

    /**
     * @dataProvider getMetadataKeys
     */
    public function testThrowsAnExceptionIfAMetadataKeyContainsAnInvalidCharacter($metadata, $fail, $invalidKey = null) {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue($metadata));

        try {
            $this->resource->validateMetadata($this->event);

            if ($fail) {
                $this->fail('Expected exception, got none');
            }
        } catch (InvalidArgumentException $e) {
            if (!$fail) {
                $this->fail('Did not expect an exception, got one');
            }

            $this->assertSame('Invalid metadata key: ' . $invalidKey, $e->getMessage());
            $this->assertSame(400, $e->getCode());
        }
    }
}
