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

use \Mockery as m;

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
    protected $driver = null;

    protected $collection = null;

    /**
     * Parameters for the driver
     */
    protected $driverParams = array(
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

        $this->collection = m::mock('\\MongoCollection');
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
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';
        $data = array('imageIdentifier' => $imageIdentifier);

        $image = m::mock('PHPIMS\\Image');
        $image->shouldReceive('getFilename', 'getFilesize', 'getMimeType')
              ->once()
              ->andReturn('some value');

        $response = m::mock('PHPIMS\\Server\\Response');

        $this->collection->shouldReceive('findOne')->once()->andReturn($data);

        $this->driver->insertImage($imageIdentifier, $image, $response);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to save image data
     */
    public function testInsertImageWhenCollectionThrowsException() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';

        $image = m::mock('PHPIMS\\Image');
        $image->shouldReceive('getFilename', 'getFilesize', 'getMimeType')
              ->once()
              ->andReturn('some value');

        $response = m::mock('PHPIMS\\Server\\Response');

        $this->collection->shouldReceive('findOne')->once()->andThrow('\\MongoException');

        $this->driver->insertImage($imageIdentifier, $image, $response);
    }

    public function testSucessfullInsert() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';
        $data = array('imageIdentifier' => $imageIdentifier);

        $image = m::mock('PHPIMS\\Image');
        $image->shouldReceive('getFilename', 'getFilesize', 'getMimeType')
              ->once()
              ->andReturn('some value');

        $response = m::mock('PHPIMS\\Server\\Response');

        $this->collection->shouldReceive('findOne')->once()->with($data)->andReturn(array());
        $this->collection->shouldReceive('insert')->once()->with(m::type('array'), m::type('array'))->andReturn(true);

        $result = $this->driver->insertImage($imageIdentifier, $image, $response);
        $this->assertTrue($result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to delete image data
     */
    public function testDeleteImageWhenCollectionThrowsAnException() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';

        $this->collection->shouldReceive('remove')->once()->with(array('imageIdentifier' => $imageIdentifier), m::type('array'))->andThrow('\\MongoException');

        $this->driver->deleteImage($imageIdentifier);
    }

    public function testSucessfullDeleteImage() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';

        $this->collection->shouldReceive('remove')->once()->with(array('imageIdentifier' => $imageIdentifier), m::type('array'))->andReturn(true);

        $result = $this->driver->deleteImage($imageIdentifier);

        $this->assertTrue($result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to edit image data
     */
    public function testUpdateMetadataWhenCollectionThrowsAnException() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );

        $this->collection->shouldReceive('update')->once()->with(array('imageIdentifier' => $imageIdentifier), array('$set' => array('metadata' => $metadata)), m::type('array'))->andThrow('\\MongoException');

        $this->driver->updateMetadata($imageIdentifier, $metadata);
    }

    public function testSucessfullUpdateMetadata() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );

        $this->collection->shouldReceive('update')->once()->with(array('imageIdentifier' => $imageIdentifier), array('$set' => array('metadata' => $metadata)), m::type('array'))->andReturn(true);

        $result = $this->driver->updateMetadata($imageIdentifier, $metadata);

        $this->assertTrue($result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to fetch image metadata
     */
    public function testGetMetadataWhenCollectionThrowsAnException() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';

        $this->collection->shouldReceive('findOne')->once()->with(array('imageIdentifier' => $imageIdentifier))->andThrow('\\MongoException');

        $this->driver->getMetadata($imageIdentifier);
    }

    public function testSucessfullGetMetadata() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );
        $data = array('metadata' => $metadata);

        $this->collection->shouldReceive('findOne')->once()->with(array('imageIdentifier' => $imageIdentifier))->andReturn($data);

        $result = $this->driver->getMetadata($imageIdentifier);

        $this->assertSame($metadata, $result);
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to remove metadata
     */
    public function testDeleteMetadataWhenCollectionThrowsAnException() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';

        $this->collection->shouldReceive('update')->once()->with(array('imageIdentifier' => $imageIdentifier), array('$set' => array('metadata' => array())), m::type('array'))->andThrow('\\MongoException');

        $this->driver->deleteMetadata($imageIdentifier);
    }

    public function testSucessfullDeleteMetadata() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';

        $this->collection->shouldReceive('update')->once()->with(array('imageIdentifier' => $imageIdentifier), array('$set' => array('metadata' => array())), m::type('array'));

        $result = $this->driver->deleteMetadata($imageIdentifier);

        $this->assertTrue($result);
    }
}