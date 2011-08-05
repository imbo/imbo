<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Database\Driver;

use Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class MongoDBTest extends \PHPUnit_Framework_TestCase {
    /**
     * Driver instance
     *
     * @var PHPIMS\Database\Driver\MongoDB
     */
    private $driver;

    /**
     * The collection to use
     *
     * @var \MongoCollection
     */
    private $collection;

    /**
     * A public key that can be used in tests
     *
     * @var string
     */
    private $publicKey = 'b73c5acc44b6a6dc907af00fd556cb1d';

    /**
     * An image identifier that can be used in tests
     *
     * @var string
     */
    private $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $driverParams = array(
        'databaseName'   => 'phpims_test',
        'collectionName' => 'images_test',
    );

    /**
     * Set up method
     */
    public function setUp() {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped(
              'The MongoDB extension is not available.'
            );
        }

        $this->collection = m::mock('MongoCollection');
        $this->driver = new MongoDB($this->driverParams, $this->collection);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->driver = null;
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Image already exists
     */
    public function testInsertImageThatAlreadyExists() {
        $data = array(
            'publicKey' => $this->publicKey,
            'imageIdentifier' => $this->imageIdentifier,
        );

        $image = m::mock('PHPIMS\Image\ImageInterface');
        $image->shouldReceive('getFilename', 'getFilesize', 'getMimeType', 'getWidth', 'getHeight')
              ->once();

        $response = m::mock('PHPIMS\Response\ResponseInterface');

        $this->collection->shouldReceive('findOne')->once()->andReturn($data);

        $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $image, $response);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to save image data
     */
    public function testInsertImageWhenCollectionThrowsException() {
        $image = m::mock('PHPIMS\Image\ImageInterface');
        $image->shouldReceive('getFilename', 'getFilesize', 'getMimeType', 'getWidth', 'getHeight')
              ->once();

        $response = m::mock('PHPIMS\Response\ResponseInterface');

        $this->collection->shouldReceive('findOne')->once()->andThrow('MongoException');

        $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $image, $response);
    }

    public function testSucessfullInsert() {
        $data = array(
            'publicKey' => $this->publicKey,
            'imageIdentifier' => $this->imageIdentifier,
        );

        $image = m::mock('PHPIMS\Image\ImageInterface');
        $image->shouldReceive('getFilename', 'getFilesize', 'getMimeType', 'getWidth', 'getHeight')
              ->once();

        $response = m::mock('PHPIMS\Response\ResponseInterface');

        $this->collection->shouldReceive('findOne')->once()->with($data)->andReturn(array());
        $this->collection->shouldReceive('insert')->once()->with(m::type('array'), m::type('array'))->andReturn(true);

        $result = $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $image, $response);
        $this->assertTrue($result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to delete image data
     */
    public function testDeleteImageWhenCollectionThrowsAnException() {
        $this->collection->shouldReceive('remove')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            m::type('array')
        )->andThrow('MongoException');

        $this->driver->deleteImage($this->publicKey, $this->imageIdentifier);
    }

    public function testSucessfullDeleteImage() {
        $this->collection->shouldReceive('remove')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            m::type('array')
        )->andReturn(true);

        $result = $this->driver->deleteImage($this->publicKey, $this->imageIdentifier);

        $this->assertTrue($result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to edit image data
     */
    public function testUpdateMetadataWhenCollectionThrowsAnException() {
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );

        $this->collection->shouldReceive('findOne')->once()->andReturn(array());
        $this->collection->shouldReceive('update')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            array('$set' => array('metadata' => $metadata)),
            m::type('array')
        )->andThrow('MongoException');

        $this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, $metadata);
    }

    public function testSucessfullUpdateMetadata() {
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );

        $this->collection->shouldReceive('findOne')->once()->andReturn(array());
        $this->collection->shouldReceive('update')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            array('$set' => array('metadata' => $metadata)),
            m::type('array')
        )->andReturn(true);

        $result = $this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, $metadata);

        $this->assertTrue($result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to fetch image metadata
     */
    public function testGetMetadataWhenCollectionThrowsAnException() {
        $this->collection->shouldReceive('findOne')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier)
        )->andThrow('MongoException');

        $this->driver->getMetadata($this->publicKey, $this->imageIdentifier);
    }

    public function testSucessfullGetMetadata() {
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );
        $data = array('metadata' => $metadata);

        $this->collection->shouldReceive('findOne')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier)
        )->andReturn($data);

        $result = $this->driver->getMetadata($this->publicKey, $this->imageIdentifier);

        $this->assertSame($metadata, $result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to remove metadata
     */
    public function testDeleteMetadataWhenCollectionThrowsAnException() {
        $this->collection->shouldReceive('update')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            array('$set' => array('metadata' => array())),
            m::type('array')
        )->andThrow('MongoException');

        $this->driver->deleteMetadata($this->publicKey, $this->imageIdentifier);
    }

    public function testSucessfullDeleteMetadata() {
        $this->collection->shouldReceive('update')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            array('$set' => array('metadata' => array())),
            m::type('array')
        );

        $result = $this->driver->deleteMetadata($this->publicKey, $this->imageIdentifier);

        $this->assertTrue($result);
    }

    public function testGetImages() {
        $query = m::mock('PHPIMS\Operation\GetImages\Query');
        $query->shouldReceive('from')->once()->andReturn(123123123);
        $query->shouldReceive('to')->once()->andReturn(234234234);
        $query->shouldReceive('query')->once()->andReturn(array('category' => 'some category'));
        $query->shouldReceive('returnMetadata')->once()->andReturn(true);
        $query->shouldReceive('num')->times(2)->andReturn(30);
        $query->shouldReceive('page')->once()->andReturn(2);

        $cursor = m::mock('MongoCursor');
        $cursor->shouldReceive('limit')->once()->with(30)->andReturn($cursor);
        $cursor->shouldReceive('sort')->once()->with(m::type('array'))->andReturn($cursor);
        $cursor->shouldReceive('skip')->once()->with(30)->andReturn($cursor);
        $cursor->shouldReceive('rewind')->once();
        $cursor->shouldReceive('valid')->times(2)->andReturn(true, false);

        $image = array('foo' => 'bar');

        $cursor->shouldReceive('current')->once()->andReturn($image);
        $cursor->shouldReceive('next')->once();

        $this->collection->shouldReceive('find')->once()->with(m::type('array'), m::type('array'))->andReturn($cursor);

        $result = $this->driver->getImages($this->publicKey, $query);

        $this->assertInternalType('array', $result);
        $this->assertSame(array($image), $result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to search for images
     */
    public function testGetImagesWhenCollectionThrowsException() {
        $query = m::mock('PHPIMS\Operation\GetImages\Query');
        $query->shouldReceive('from')->once();
        $query->shouldReceive('to')->once();
        $query->shouldReceive('query')->once();
        $query->shouldReceive('returnMetadata')->once();

        $this->collection->shouldReceive('find')->once()->with(m::type('array'), m::type('array'))->andThrow('MongoException');

        $this->driver->getImages($this->publicKey, $query);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to fetch image data
     */
    public function testLoadWhenCollectionThrowsException() {
        $this->collection->shouldReceive('findOne')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            m::type('array')
        )->andThrow('MongoException');

        $this->driver->load($this->publicKey, $this->imageIdentifier, m::mock('PHPIMS\Image\ImageInterface'));
    }

    public function testSucessfullLoad() {
        $data = array(
            'name' => 'filename',
            'size' => 123,
            'width' => 234,
            'height' => 345,
            'mime' => 'image/jpg',
        );

        $image = m::mock('PHPIMS\Image\ImageInterface');
        $image->shouldReceive('setFilesize')->once()->with($data['size'])->andReturn($image);
        $image->shouldReceive('setWidth')->once()->with($data['width'])->andReturn($image);
        $image->shouldReceive('setHeight')->once()->with($data['height'])->andReturn($image);
        $image->shouldReceive('setMimeType')->once()->with($data['mime'])->andReturn($image);

        $this->collection->shouldReceive('findOne')->once()->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            m::type('array')
        )->andReturn($data);

        $this->assertTrue($this->driver->load($this->publicKey, $this->imageIdentifier, $image));
    }
}
