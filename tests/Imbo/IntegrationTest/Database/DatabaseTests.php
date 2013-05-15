<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Database;

use Imbo\Model\Image,
    Imbo\Resource\Images\Query;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
abstract class DatabaseTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Database\DatabaseInterface
     */
    private $driver;

    /**
     * @var string
     */
    private $publicKey = 'key';

    /**
     * @var string
     */
    private $imageIdentifier = '9cb263819af35064af0b6665a1b0fddd';

    /**
     * @var Image
     */
    private $image;

    /**
     * @var string
     */
    private $imageData;

    /**
     * Get the driver we want to test
     *
     * @return Imbo\Database\DatabaseInterface
     */
    abstract protected function getDriver();

    /**
     * Set up
     */
    public function setUp() {
        $this->imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $this->image = $this->getMock('Imbo\Model\Image');
        $this->image->expects($this->any())->method('getFilesize')->will($this->returnValue(strlen($this->imageData)));
        $this->image->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $this->image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/png'));
        $this->image->expects($this->any())->method('getWidth')->will($this->returnValue(665));
        $this->image->expects($this->any())->method('getHeight')->will($this->returnValue(463));
        $this->image->expects($this->any())->method('getBlob')->will($this->returnValue($this->imageData));

        $this->driver = $this->getDriver();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->imageData = null;
        $this->image = null;
        $this->driver = null;
    }

    public function testInsertAndGetImage() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setWidth')->with(665)->will($this->returnSelf());
        $image->expects($this->once())->method('setHeight')->with(463)->will($this->returnSelf());
        $image->expects($this->once())->method('setMimeType')->with('image/png')->will($this->returnSelf());
        $image->expects($this->once())->method('setExtension')->with('png')->will($this->returnSelf());
        $image->expects($this->once())->method('setAddedDate')->with($this->isInstanceOf('DateTime'))->will($this->returnSelf());
        $image->expects($this->once())->method('setUpdatedDate')->with($this->isInstanceOf('DateTime'))->will($this->returnSelf());

        $this->assertTrue($this->driver->load($this->publicKey, $this->imageIdentifier, $image));
    }

    public function testStoreSameImageTwice() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $lastModified1 = $this->driver->getLastModified($this->publicKey, $this->imageIdentifier);
        sleep(1);
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $lastModified2 = $this->driver->getLastModified($this->publicKey, $this->imageIdentifier);
        $this->assertTrue($lastModified2 > $lastModified1);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testStoreDeleteAndGetImage() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertTrue($this->driver->deleteImage($this->publicKey, $this->imageIdentifier));
        $this->driver->load($this->publicKey, $this->imageIdentifier, $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testDeleteImageThatDoesNotExist() {
        $this->driver->deleteImage($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testLoadImageThatDoesNotExist() {
        $this->driver->load($this->publicKey, $this->imageIdentifier, $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetLastModifiedOfImageThatDoesNotExist() {
        $this->driver->getLastModified($this->publicKey, 'foobar');
    }

    public function testGetLastModified() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->publicKey, $this->imageIdentifier));
    }

    public function testGetLastModifiedWhenUserHasNoImages() {
        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->publicKey));
    }

    public function testGetNumImages() {
        $this->assertSame(0, $this->driver->getNumImages($this->publicKey));
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertSame(1, $this->driver->getNumImages($this->publicKey));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetMetadataWhenImageDoesNotExist() {
        $this->driver->getMetadata($this->publicKey, $this->imageIdentifier);
    }

    public function testGetMetadataWhenImageHasNone() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertSame(array(), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
    }

    public function testUpdateAndGetMetadata() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertTrue($this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, array('foo' => 'bar')));
        $this->assertSame(array('foo' => 'bar'), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
        $this->assertTrue($this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, array('foo' => 'foo', 'bar' => 'foo')));
        $this->assertSame(array('foo' => 'foo', 'bar' => 'foo'), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
    }

    public function testUpdateDeleteAndGetMetadata() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertTrue($this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, array('foo' => 'bar')));
        $this->assertSame(array('foo' => 'bar'), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
        $this->assertTrue($this->driver->deleteMetadata($this->publicKey, $this->imageIdentifier));
        $this->assertSame(array(), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testDeleteMetataFromImageThatDoesNotExist() {
        $this->driver->deleteMetadata($this->publicKey, $this->imageIdentifier);
    }

    private function insertImages() {
        $images = array();

        $images[0] = new Image();
        $images[0]->setMimeType('image/png')->setExtension('png')->setWidth(665)->setHeight(463)->setBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $images[1] = new Image();
        $images[1]->setMimeType('image/png')->setExtension('png')->setWidth(599)->setHeight(417)->setBlob(file_get_contents(FIXTURES_DIR . '/image1.png'));

        $images[2] = new Image();
        $images[2]->setMimeType('image/png')->setExtension('png')->setWidth(539)->setHeight(375)->setBlob(file_get_contents(FIXTURES_DIR . '/image2.png'));

        $images[3] = new Image();
        $images[3]->setMimeType('image/png')->setExtension('png')->setWidth(485)->setHeight(338)->setBlob(file_get_contents(FIXTURES_DIR . '/image3.png'));

        $images[4] = new Image();
        $images[4]->setMimeType('image/png')->setExtension('png')->setWidth(437)->setHeight(304)->setBlob(file_get_contents(FIXTURES_DIR . '/image4.png'));

        $start = time();
        sleep(1);

        foreach ($images as $index => $image) {
            $imageIdentifier = md5($image->getBlob());

            $this->driver->insertImage($this->publicKey, $imageIdentifier, $image);
            $metadata = array('key' . $index => 'value' . $index);
            $this->driver->updateMetadata($this->publicKey, $imageIdentifier, $metadata);

            sleep(1);
        }

        $end = time();

        return array($start, $end);
    }

    public function testGetImages() {
        list($start, $end) = $this->insertImages();

        // Empty query
        $query = new Query();
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(5, $images);

        // Query with end timestamp
        $query = new Query();
        $query->to($end);

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(5, $images);

        $query = new Query();
        $query->to($start);

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(0, $images);

        // Query with start timestamp
        $query = new Query();
        $query->from($start);

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(5, $images);

        $query = new Query();
        $query->from($end);

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(0, $images);

        // Make sure the result has the correct keys
        $query = new Query();
        $images = $this->driver->getImages($this->publicKey, $query);

        foreach (array('added', 'updated', 'extension', 'height', 'width', 'imageIdentifier', 'mime', 'publicKey', 'size') as $key) {
            $this->assertArrayHasKey($key, $images[0]);
        }

        $query = new Query();
        $query->returnMetadata(true);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertArrayHasKey('metadata', $images[0]);
        $this->assertArrayHasKey('metadata', $images[1]);
        $this->assertArrayHasKey('metadata', $images[2]);
        $this->assertArrayHasKey('metadata', $images[3]);
        $this->assertArrayHasKey('metadata', $images[4]);
        $this->assertSame(array('key4' => 'value4'), $images[0]['metadata']);
        $this->assertSame(array('key3' => 'value3'), $images[1]['metadata']);
        $this->assertSame(array('key2' => 'value2'), $images[2]['metadata']);
        $this->assertSame(array('key1' => 'value1'), $images[3]['metadata']);
        $this->assertSame(array('key0' => 'value0'), $images[4]['metadata']);

        foreach (array('added', 'updated') as $dateField) {
            for ($i = 0; $i < 5; $i++) {
                $this->assertInstanceOf('DateTime', $images[$i][$dateField]);
            }
        }

        // Test page and limit
        $query = new Query();
        $query->limit(2);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(2, $images);

        $query = new Query();
        $query->limit(2)->page(1);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(2, $images);
        $this->assertSame('a501051db16e3cbf88ea50bfb0138a47', $images[0]['imageIdentifier']);
        $this->assertSame('1d5b88aec8a3e1c4c57071307b2dae3a', $images[1]['imageIdentifier']);

        $query = new Query();
        $query->limit(2)->page(2);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(2, $images);
        $this->assertSame('b914b28f4d5faa516e2049b9a6a2577c', $images[0]['imageIdentifier']);
        $this->assertSame('fc7d2d06993047a0b5056e8fac4462a2', $images[1]['imageIdentifier']);

        $query = new Query();
        $query->limit(2)->page(3);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(1, $images);
        $this->assertSame('929db9c5fc3099f7576f5655207eba47', $images[0]['imageIdentifier']);

        $query = new Query();
        $query->limit(2)->page(4);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertSame(array(), $images);

        $query = new Query();
        $query->metadataQuery(array('key2' => 'value2'));
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(1, $images);
        $this->assertSame('b914b28f4d5faa516e2049b9a6a2577c', $images[0]['imageIdentifier']);
    }

    public function testGetImageMimeType() {
        $images = array();

        $images[0] = new Image();
        $images[0]->setMimeType('image/png')->setExtension('png')->setWidth(665)->setHeight(463)->setBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $images[1] = new Image();
        $images[1]->setMimeType('image/jpeg')->setExtension('jpg')->setWidth(665)->setHeight(463)->setBlob(file_get_contents(FIXTURES_DIR . '/image.jpg'));

        foreach ($images as $image) {
            $imageIdentifier = md5($image->getBlob());

            $this->driver->insertImage($this->publicKey, $imageIdentifier, $image);
        }

        $this->assertSame('image/png', $this->driver->getImageMimeType($this->publicKey, md5($images[0]->getBlob())));
        $this->assertSame('image/jpeg', $this->driver->getImageMimeType($this->publicKey, md5($images[1]->getBlob())));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetMimeTypeWhenImageDoesNotExist() {
        $this->driver->getImageMimeType($this->publicKey, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    }

    public function testCanCheckIfImageAlreadyExists() {
        $this->assertFalse($this->driver->imageExists($this->publicKey, $this->imageIdentifier));
        $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image);
        $this->assertTrue($this->driver->imageExists($this->publicKey, $this->imageIdentifier));
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getShortUrlVariations() {
        return array(
            'without query and extension' => array(
                'aaaaaaa',
            ),
            'with query and extension' => array(
                'bbbbbbb',
                array(
                    't' => array(
                        'thumbnail:width=40'
                    ),
                    'accessToken' => 'token',
                ),
                'png',
            ),
            'with query' => array(
                'ccccccc',
                array(
                    't' => array(
                        'thumbnail:width=40'
                    ),
                    'accessToken' => 'token',
                ),
            ),
            'with extension' => array(
                'ddddddd',
                array(),
                'gif',
            ),
        );
    }

    /**
     * @dataProvider getShortUrlVariations
     */
    public function testCanInsertAndGetParametersForAShortUrl($shortUrlId, array $query = array(), $extension = null) {
        $this->assertTrue($this->driver->insertShortUrl($shortUrlId, $this->publicKey, $this->imageIdentifier, $extension, $query));

        $params = $this->driver->getShortUrlParams($shortUrlId);

        $this->assertSame($this->publicKey, $params['publicKey']);
        $this->assertSame($this->imageIdentifier, $params['imageIdentifier']);
        $this->assertSame($extension, $params['extension']);
        $this->assertSame($query, $params['query']);

        $this->assertSame($shortUrlId, $this->driver->getShortUrlId($this->publicKey, $this->imageIdentifier, $extension, $query));
    }

    public function testCanDeleteShortUrls() {
        $shortUrlId = 'aaaaaaa';

        $this->assertTrue($this->driver->insertShortUrl($shortUrlId, $this->publicKey, $this->imageIdentifier));
        $this->assertTrue($this->driver->deleteShortUrls($this->publicKey, $this->imageIdentifier));
        $this->assertNull($this->driver->getShortUrlParams($shortUrlId));
    }

    public function testCanFilterOnImageIdentifiers() {
        $publicKey = 'christer';
        $id1 = str_repeat('a', 32);
        $id2 = str_repeat('b', 32);
        $id3 = str_repeat('c', 32);
        $id4 = str_repeat('d', 32);
        $id5 = str_repeat('e', 32);

        $this->assertTrue($this->driver->insertImage($publicKey, $id1, $this->image));
        $this->assertTrue($this->driver->insertImage($publicKey, $id2, $this->image));
        $this->assertTrue($this->driver->insertImage($publicKey, $id3, $this->image));
        $this->assertTrue($this->driver->insertImage($publicKey, $id4, $this->image));
        $this->assertTrue($this->driver->insertImage($publicKey, $id5, $this->image));

        $query = new Query();

        $query->imageIdentifiers(array($id1));
        $this->assertCount(1, $this->driver->getImages($publicKey, $query));

        $query->imageIdentifiers(array($id1, $id2));
        $this->assertCount(2, $this->driver->getImages($publicKey, $query));

        $query->imageIdentifiers(array($id1, $id2, $id3));
        $this->assertCount(3, $this->driver->getImages($publicKey, $query));

        $query->imageIdentifiers(array($id1, $id2, $id3, $id4));
        $this->assertCount(4, $this->driver->getImages($publicKey, $query));

        $query->imageIdentifiers(array($id1, $id2, $id3, $id4, $id5));
        $this->assertCount(5, $this->driver->getImages($publicKey, $query));

        $query->imageIdentifiers(array($id1, $id2, $id3, $id4, $id5, str_repeat('f', 32)));
        $this->assertCount(5, $this->driver->getImages($publicKey, $query));
    }

    public function testCanGetNumberOfBytes() {
        $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image);
        $this->assertSame(41423, $this->driver->getNumBytes($this->publicKey));
    }
}
