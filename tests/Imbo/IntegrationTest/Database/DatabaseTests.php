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
    Imbo\Resource\Images\Query,
    DateTime,
    DateTimeZone;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 * @group integration
 * @group database
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
     * Get the driver we want to test
     *
     * @return Imbo\Database\DatabaseInterface
     */
    abstract protected function getDriver();

    /**
     * Set up
     */
    public function setUp() {
        $filePath = FIXTURES_DIR . '/image.png';
        $imageInfo = getimagesize($filePath);

        $this->image = new Image();
        $this->image->setBlob(file_get_contents($filePath))
                    ->setExtension('png')
                    ->setMimeType($imageInfo['mime'])
                    ->setWidth($imageInfo[0])
                    ->setHeight($imageInfo[1]);

        $this->driver = $this->getDriver();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->image = null;
        $this->driver = null;
    }

    public function testInsertAndGetImage() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));

        $image = new Image();
        $this->assertTrue($this->driver->load($this->publicKey, $this->imageIdentifier, $image));

        $this->assertSame($image->getWidth(), $this->image->getWidth());
        $this->assertSame($image->getHeight(), $this->image->getHeight());
        $this->assertSame($image->getMimeType(), $this->image->getMimeType());
        $this->assertSame($image->getExtension(), $this->image->getExtension());
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
        $now = time();
        $start = $now;
        $images = array();

        foreach (array('image.jpg', 'image.png', 'image1.png', 'image2.png', 'image3.png', 'image4.png') as $i => $fileName) {
            $path = FIXTURES_DIR . '/' . $fileName;
            $info = getimagesize($path);

            $images[$i] = new Image();
            $images[$i]->setMimeType($info['mime'])
                       ->setExtension(substr($fileName, strrpos($fileName, '.') + 1))
                       ->setWidth($info[0])
                       ->setHeight($info[1])
                       ->setBlob(file_get_contents($path))
                       ->setAddedDate(new DateTime('@' . $now++, new DateTimeZone('UTC')));
        }

        foreach ($images as $index => $image) {
            $imageIdentifier = md5($image->getBlob());

            $this->driver->insertImage($this->publicKey, $imageIdentifier, $image);
            $metadata = array('key' . $index => 'value' . $index);
            $this->driver->updateMetadata($this->publicKey, $imageIdentifier, $metadata);
        }

        // Remove the last increment to get the timestamp for when the last image was added
        $end = $now - 1;

        return array($start, $end);
    }

    public function testGetImagesWithNoQuery() {
        list($start, $end) = $this->insertImages();

        // Empty query
        $query = new Query();
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(6, $images);
    }

    public function testGetImagesWithStartAndEndTimestamps() {
        list($start, $end) = $this->insertImages();

        // Fetch to the timestamp of when the last image was added
        $query = new Query();
        $query->to($end);
        $this->assertCount(6, $this->driver->getImages($this->publicKey, $query));

        // Fetch until the second the first image was added
        $query = new Query();
        $query->to($start);
        $this->assertCount(1, $this->driver->getImages($this->publicKey, $query));

        // Fetch from the second the first image was added
        $query = new Query();
        $query->from($start);
        $this->assertCount(6, $this->driver->getImages($this->publicKey, $query));

        // Fetch from the second the last image was added
        $query = new Query();
        $query->from($end);
        $this->assertCount(1, $this->driver->getImages($this->publicKey, $query));
    }

    public function testGetImagesAndReturnMetadata() {
        $this->insertImages();

        $query = new Query();
        $query->returnMetadata(true);

        $images = $this->driver->getImages($this->publicKey, $query);

        foreach ($images as $image) {
            $this->assertArrayHasKey('metadata', $image);
        }

        $this->assertSame(array('key5' => 'value5'), $images[0]['metadata']);
        $this->assertSame(array('key4' => 'value4'), $images[1]['metadata']);
        $this->assertSame(array('key3' => 'value3'), $images[2]['metadata']);
        $this->assertSame(array('key2' => 'value2'), $images[3]['metadata']);
        $this->assertSame(array('key1' => 'value1'), $images[4]['metadata']);
        $this->assertSame(array('key0' => 'value0'), $images[5]['metadata']);

    }

    public function testGetImagesReturnsImagesWithDateTimeInstances() {
        $this->insertImages();

        $images = $this->driver->getImages($this->publicKey, new Query());

        foreach (array('added', 'updated') as $dateField) {
            foreach ($images as $image) {
                $this->assertInstanceOf('DateTime', $image[$dateField]);
            }
        }
    }

    public function getPageAndLimit() {
        return array(
            'no page or limit' => array(null, null, array(
                'a501051db16e3cbf88ea50bfb0138a47',
                '1d5b88aec8a3e1c4c57071307b2dae3a',
                'b914b28f4d5faa516e2049b9a6a2577c',
                'fc7d2d06993047a0b5056e8fac4462a2',
                '929db9c5fc3099f7576f5655207eba47',
                'f3210f1bb34bfbfa432cc3560be40761',
            )),
            'no page, 2 images' => array(null, 2, array(
                'a501051db16e3cbf88ea50bfb0138a47',
                '1d5b88aec8a3e1c4c57071307b2dae3a',
            )),
            'first page, 2 images' => array(1, 2, array(
                'a501051db16e3cbf88ea50bfb0138a47',
                '1d5b88aec8a3e1c4c57071307b2dae3a',
            )),
            'second page, 2 images' => array(2, 2, array(
                'b914b28f4d5faa516e2049b9a6a2577c',
                'fc7d2d06993047a0b5056e8fac4462a2',
            )),
            'second page, 4 images' => array(2, 4, array(
                '929db9c5fc3099f7576f5655207eba47',
                'f3210f1bb34bfbfa432cc3560be40761',
            )),
            'fourth page, 2 images' => array(4, 2, array()),
        );
    }

    /**
     * @dataProvider getPageAndLimit
     */
    public function testGetImagesWithPageAndLimit($page = null, $limit = null, array $imageIdentifiers) {
        $this->insertImages();

        // Test page and limit
        $query = new Query();

        if ($page !== null) {
            $query->page($page);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(count($imageIdentifiers), $images);

        foreach ($images as $i => $image) {
            $this->assertSame($imageIdentifiers[$i], $image['imageIdentifier']);
        }
    }

    public function testGetImagesWithMetadataQuery() {
        $this->insertImages();

        $query = new Query();
        $query->metadataQuery(array('key2' => 'value2'));
        $images = $this->driver->getImages($this->publicKey, $query);

        $this->assertCount(1, $images);
        $this->assertSame('fc7d2d06993047a0b5056e8fac4462a2', $images[0]['imageIdentifier']);
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

    public function getSortData() {
        return array(
            'no sorting' => array(
                null,
                'imageIdentifier',
                array(
                    'a501051db16e3cbf88ea50bfb0138a47',
                    '1d5b88aec8a3e1c4c57071307b2dae3a',
                    'b914b28f4d5faa516e2049b9a6a2577c',
                    'fc7d2d06993047a0b5056e8fac4462a2',
                    '929db9c5fc3099f7576f5655207eba47',
                    'f3210f1bb34bfbfa432cc3560be40761',
                ),
            ),
            'default sort on size' => array(
                'size',
                'size',
                array(
                    41423,
                    64828,
                    74337,
                    84988,
                    92795,
                    95576,
                ),
            ),
            'desc sort on size' => array(
                'size:desc',
                'size',
                array(
                    95576,
                    92795,
                    84988,
                    74337,
                    64828,
                    41423,
                ),
            ),
            'sort on multiple fields' => array(
                'width:asc,size:desc',
                'size',
                array(
                    74337,
                    84988,
                    92795,
                    95576,
                    64828,
                    41423,
                ),
            ),
        );
    }

    /**
     * @dataProvider getSortData
     */
    public function testCanSortImages($sort = null, $field, array $values) {
        $this->insertImages();

        $query = new Query();

        if ($sort !== null) {
            $query->sort($sort);
        }

        $images = $this->driver->getImages($this->publicKey, $query);

        foreach ($images as $i => $image) {
            $this->assertSame($values[$i], $image[$field]);
        }
    }
}
