<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Database;

use MongoException;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Database\MongoDB
 */
class MongoDBTest extends \PHPUnit_Framework_TestCase {
    /**
     * Driver instance
     *
     * @var Imbo\Database\MongoDB
     */
    private $driver;

    /**
     * Mongo instance
     *
     * @var Mongo
     */
    private $mongo;

    /**
     * The collection to use
     *
     * @var MongoCollection
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
        'databaseName'   => 'imbo_test',
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

        $this->mongo = $this->getMockBuilder('Mongo')->disableOriginalConstructor()->getMock();
        $this->collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $this->driver = new MongoDB($this->driverParams, $this->mongo, $this->collection);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->driver = null;
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Image already exists
     * @covers Imbo\Database\MongoDB::insertImage
     */
    public function testInsertImageThatAlreadyExists() {
        $data = array(
            'publicKey' => $this->publicKey,
            'imageIdentifier' => $this->imageIdentifier,
        );

        $this->collection->expects($this->once())->method('findOne')->will($this->returnValue($data));

        $image = $this->getMock('Imbo\Image\ImageInterface');
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $image, $response);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to save image data
     * @covers Imbo\Database\MongoDB::insertImage
     */
    public function testInsertImageWhenCollectionThrowsException() {
        $this->collection->expects($this->once())->method('findOne')->will($this->throwException(new MongoException()));

        $image = $this->getMock('Imbo\Image\ImageInterface');
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $image, $response);
    }

    /**
     * @covers Imbo\Database\MongoDB::insertImage
     */
    public function testSuccessfulInsert() {
        $data = array(
            'publicKey' => $this->publicKey,
            'imageIdentifier' => $this->imageIdentifier,
        );

        $image = $this->getMock('Imbo\Image\ImageInterface');
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->collection->expects($this->once())->method('findOne')->with($data)->will($this->returnValue(array()));
        $this->collection->expects($this->once())->method('insert')->with($this->isType('array'), $this->isType('array'))->will($this->returnValue(true));

        $result = $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $image, $response);
        $this->assertTrue($result);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to delete image data
     * @covers Imbo\Database\MongoDB::deleteImage
     */
    public function testDeleteImageWhenCollectionThrowsAnException() {
        $this->collection->expects($this->once())
                         ->method('remove')
                         ->with(
                             array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
                             $this->isType('array'))
                         ->will($this->throwException(new MongoException()));

        $this->driver->deleteImage($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Database\MongoDB::deleteImage
     */
    public function testSuccessfulDeleteImage() {
        $this->collection->expects($this->once())
                         ->method('remove')
                         ->with(
                             array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
                             $this->isType('array'))
                         ->will($this->returnValue(true));

        $result = $this->driver->deleteImage($this->publicKey, $this->imageIdentifier);

        $this->assertTrue($result);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to edit image data
     * @covers Imbo\Database\MongoDB::updateMetadata
     */
    public function testUpdateMetadataWhenCollectionThrowsAnException() {
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );

        $this->collection->expects($this->once())->method('findOne')->will($this->returnValue(array()));
        $this->collection->expects($this->once())
                         ->method('update')
                         ->with(
                             array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
                             $this->isType('array'),
                             $this->isType('array'))
                         ->will($this->throwException(new MongoException()));

        $this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, $metadata);
    }

    /**
     * @covers Imbo\Database\MongoDB::updateMetadata
     */
    public function testSuccessfulUpdateMetadata() {
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );

        $this->collection->expects($this->once())->method('findOne')->will($this->returnValue(array()));
        $this->collection->expects($this->once())
                         ->method('update')
                         ->with(
                             array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
                             $this->isType('array'),
                             $this->isType('array'))
                         ->will($this->returnValue(true));

        $result = $this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, $metadata);

        $this->assertTrue($result);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to fetch image metadata
     * @covers Imbo\Database\MongoDB::getMetadata
     */
    public function testGetMetadataWhenCollectionThrowsAnException() {
        $this->collection->expects($this->once())->method('findOne')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier)
        )->will($this->throwException(new MongoException()));

        $this->driver->getMetadata($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     * @covers Imbo\Database\MongoDB::getMetadata
     */
    public function testGetMetadataWhenEntryDoesNotExist() {
        $this->collection->expects($this->once())->method('findOne')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier)
        )->will($this->returnValue(null));

        $this->driver->getMetadata($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Database\MongoDB::getMetadata
     */
    public function testSuccessfulGetMetadata() {
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foobar' => 42,
            ),
        );
        $data = array('metadata' => $metadata);

        $this->collection->expects($this->once())->method('findOne')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier)
        )->will($this->returnValue($data));

        $result = $this->driver->getMetadata($this->publicKey, $this->imageIdentifier);

        $this->assertSame($metadata, $result);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to remove metadata
     * @covers Imbo\Database\MongoDB::deleteMetadata
     */
    public function testDeleteMetadataWhenCollectionThrowsAnException() {
        $this->collection->expects($this->once())->method('update')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            array('$set' => array('metadata' => array())),
            $this->isType('array')
        )->will($this->throwException(new MongoException()));

        $this->driver->deleteMetadata($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Database\MongoDB::deleteMetadata
     */
    public function testSuccessfulDeleteMetadata() {
        $this->collection->expects($this->once())->method('update')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            array('$set' => array('metadata' => array())),
            $this->isType('array')
        );

        $result = $this->driver->deleteMetadata($this->publicKey, $this->imageIdentifier);

        $this->assertTrue($result);
    }

    /**
     * @covers Imbo\Database\MongoDB::getImages
     */
    public function testGetImages() {
        $query = $this->getMock('Imbo\Resource\Images\Query');
        $query->expects($this->once())->method('from')->will($this->returnValue(123123123));
        $query->expects($this->once())->method('to')->will($this->returnValue(234234234));
        $query->expects($this->once())->method('metadataQuery')->will($this->returnValue(array('category' => 'some category')));
        $query->expects($this->once())->method('returnMetadata')->will($this->returnValue(true));
        $query->expects($this->exactly(2))->method('limit')->will($this->returnValue(30));
        $query->expects($this->once())->method('page')->will($this->returnValue(2));

        $cursor = $this->getMockBuilder('MongoCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('limit')->with(30)->will($this->returnValue($cursor));
        $cursor->expects($this->once())->method('sort')->with($this->isType('array'))->will($this->returnValue($cursor));
        $cursor->expects($this->once())->method('skip')->with(30)->will($this->returnValue($cursor));
        $cursor->expects($this->once())->method('rewind');
        $cursor->expects($this->exactly(2))->method('valid')->will($this->onConsecutiveCalls(true, false));

        $image = array('foo' => 'bar');

        $cursor->expects($this->once())->method('current')->will($this->returnValue($image));
        $cursor->expects($this->once())->methoD('next');

        $this->collection->expects($this->once())->method('find')->with($this->isType('array'), $this->isType('array'))->will($this->returnValue($cursor));

        $result = $this->driver->getImages($this->publicKey, $query);

        $this->assertInternalType('array', $result);
        $this->assertSame(array($image), $result);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to search for images
     * @covers Imbo\Database\MongoDB::getImages
     */
    public function testGetImagesWhenCollectionThrowsException() {
        $query = $this->getMock('Imbo\Resource\Images\Query');

        foreach (array('from', 'to', 'metadataQuery', 'returnMetadata') as $method) {
            $query->expects($this->once())->method($method);
        }

        $this->collection->expects($this->once())->method('find')->with($this->isType('array'), $this->isType('array'))->will($this->throwException(new MongoException()));

        $this->driver->getImages($this->publicKey, $query);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to fetch image data
     * @covers Imbo\Database\MongoDB::load
     */
    public function testLoadWhenCollectionThrowsException() {
        $this->collection->expects($this->once())->method('findOne')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            $this->isType('array')
        )->will($this->throwException(new MongoException()));

        $this->driver->load($this->publicKey, $this->imageIdentifier, $this->getMock('Imbo\Image\ImageInterface'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     * @covers Imbo\Database\MongoDB::load
     */
    public function testLoadWhenImageDoesNotExist() {
        $this->collection->expects($this->once())->method('findOne')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            $this->isType('array')
        )->will($this->returnValue(null));

        $this->driver->load($this->publicKey, $this->imageIdentifier, $this->getMock('Imbo\Image\ImageInterface'));
    }

    /**
     * @covers Imbo\Database\MongoDB::load
     */
    public function testSuccessfulLoad() {
        $data = array(
            'name' => 'filename',
            'size' => 123,
            'width' => 234,
            'height' => 345,
            'mime' => 'image/jpg',
            'extension' => 'jpg',
        );

        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('setWidth')->with($data['width'])  ->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($data['height'])->will($this->returnValue($image));
        $image->expects($this->once())->method('setMimeType')->with($data['mime'])->will($this->returnValue($image));

        $this->collection->expects($this->once())->method('findOne')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            $this->isType('array')
        )->will($this->returnValue($data));

        $this->assertTrue($this->driver->load($this->publicKey, $this->imageIdentifier, $image));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Unable to fetch image data
     * @covers Imbo\Database\MongoDB::getLastModified
     */
    public function testGetLastModifiedWhenCollectionThrowsException() {
        $this->collection->expects($this->once())->method('find')->with(
            array('publicKey' => $this->publicKey),
            array('updated')
        )->will($this->throwException(new MongoException()));

        $this->driver->getLastModified($this->publicKey);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     * @covers Imbo\Database\MongoDB::getLastModified
     */
    public function testGetLastModifiedWhenImageDoesNotExist() {
        $cursor = $this->getMockBuilder('MongoCursor')->disableOriginalConstructor()->setMethods(array('limit', 'sort', 'getNext'))->getMock();
        $cursor->expects($this->any())->method('limit')->will($this->returnValue($cursor));
        $cursor->expects($this->any())->method('sort')->will($this->returnValue($cursor));
        $cursor->expects($this->any())->method('getNext')->will($this->returnValue(null));

        $this->collection->expects($this->once())->method('find')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            array('updated')
        )->will($this->returnValue($cursor));

        $this->driver->getLastModified($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Database\MongoDB::getLastModified
     */
    public function testGetLastModifiedWhenUserDoesNotHaveAnyImages() {
        $cursor = $this->getMockBuilder('MongoCursor')->disableOriginalConstructor()->setMethods(array('limit', 'sort', 'getNext'))->getMock();
        $cursor->expects($this->any())->method('limit')->will($this->returnValue($cursor));
        $cursor->expects($this->any())->method('sort')->will($this->returnValue($cursor));
        $cursor->expects($this->any())->method('getNext')->will($this->returnValue(null));

        $this->collection->expects($this->once())->method('find')->with(
            array('publicKey' => $this->publicKey),
            array('updated')
        )->will($this->returnValue($cursor));

        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->publicKey));
    }

    /**
     * @covers Imbo\Database\MongoDB::getLastModified
     */
    public function testGetLastModifiedWithoutImageIdentifier() {
        $now = time();

        $data = array(
            'updated' => $now,
        );

        $cursor = $this->getMockBuilder('MongoCursor')->disableOriginalConstructor()->setMethods(array('limit', 'sort', 'getNext'))->getMock();
        $cursor->expects($this->any())->method('limit')->will($this->returnValue($cursor));
        $cursor->expects($this->any())->method('sort')->will($this->returnValue($cursor));
        $cursor->expects($this->any())->method('getNext')->will($this->returnValue($data));

        $this->collection->expects($this->any())->method('find')->with(
            array('publicKey' => $this->publicKey),
            array('updated')
        )->will($this->returnValue($cursor));

        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->publicKey));
        $formatted = $this->driver->getLastModified($this->publicKey, null, true);

        $this->assertSame($now, strtotime($formatted));
    }

    /**
     * @covers Imbo\Database\MongoDB::getLastModified
     */
    public function testGetLastModifiedWithImageIdentifier() {
        $now = time();

        $data = array(
            'updated' => $now,
        );

        $cursor = $this->getMockBuilder('MongoCursor')->disableOriginalConstructor()->setMethods(array('limit', 'sort', 'getNext'))->getMock();
        $cursor->expects($this->any())->method('limit')->will($this->returnValue($cursor));
        $cursor->expects($this->any())->method('sort')->will($this->returnValue($cursor));
        $cursor->expects($this->any())->method('getNext')->will($this->returnValue($data));

        $this->collection->expects($this->any())->method('find')->with(
            array('publicKey' => $this->publicKey, 'imageIdentifier' => $this->imageIdentifier),
            array('updated')
        )->will($this->returnValue($cursor));

        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->publicKey, $this->imageIdentifier));
        $formatted = $this->driver->getLastModified($this->publicKey, $this->imageIdentifier, true);

        $this->assertSame($now, strtotime($formatted));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @covers Imbo\Database\MongoDB::getNumImages
     */
    public function testGetNumImagesWhenMongoThrowsAnException() {
        $this->collection->expects($this->once())->method('find')->will($this->throwException(new MongoException()));
        $this->driver->getNumImages($this->publicKey);
    }

    /**
     * @covers Imbo\Database\MongoDB::getNumImages
     */
    public function testGetNumImages() {
        $result = $this->getMock('stdClass', array('count'));
        $result->expects($this->once())->method('count')->will($this->returnValue(2));
        $this->collection->expects($this->once())->method('find')->with(array('publicKey' => $this->publicKey))->will($this->returnValue($result));
        $this->assertSame(2, $this->driver->getNumImages($this->publicKey));
    }
}
