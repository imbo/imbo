<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Database;

use Imbo\Model\Image,
    Imbo\Model\Images,
    Imbo\Resource\Images\Query,
    DateTime,
    DateTimeZone;

/**
 * @group integration
 * @group database
 */
abstract class DatabaseTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Database\DatabaseInterface
     */
    private $adapter;

    /**
     * Get the adapter we want to test
     *
     * @return Imbo\Database\DatabaseInterface
     */
    abstract protected function getAdapter();

    /**
     * Set up
     */
    public function setUp() {
        $this->adapter = $this->getAdapter();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->adapter = null;
    }

    /**
     * Fetch an image model
     *
     * @return Image
     */
    protected function getImage() {
        return (new Image())->setBlob('imageblob')
                            ->setWidth(123)
                            ->setHeight(234)
                            ->setFilesize(3456)
                            ->setMimeType('image/jpeg')
                            ->setExtension('jpg')
                            ->setOriginalChecksum(md5(__FILE__));
    }

    public function testInsertAndGetImage() {
        $publicKey = 'key';
        $imageIdentifier = 'id';
        $originalImage = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($publicKey, $imageIdentifier, $originalImage));

        $image = new Image();
        $this->assertTrue($this->adapter->load($publicKey, $imageIdentifier, $image));

        $this->assertSame($originalImage->getWidth(), $image->getWidth());
        $this->assertSame($originalImage->getHeight(), $image->getHeight());
        $this->assertSame($originalImage->getMimeType(), $image->getMimeType());
        $this->assertSame($originalImage->getFilesize(), $image->getFilesize());
        $this->assertSame($originalImage->getExtension(), $image->getExtension());
    }

    public function testStoreSameImageTwice() {
        $publicKey = 'key';
        $imageIdentifier = 'id';
        $image = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($publicKey, $imageIdentifier, $image));
        $lastModified1 = $this->adapter->getLastModified($publicKey, $imageIdentifier);

        sleep(1);

        $this->assertTrue($this->adapter->insertImage($publicKey, $imageIdentifier, $image));
        $lastModified2 = $this->adapter->getLastModified($publicKey, $imageIdentifier);

        $this->assertTrue($lastModified2 > $lastModified1);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testStoreDeleteAndGetImage() {
        $publicKey = 'key';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($publicKey, $imageIdentifier, $this->getImage()));
        $this->assertTrue($this->adapter->deleteImage($publicKey, $imageIdentifier));

        $this->adapter->load($publicKey, $imageIdentifier, $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testDeleteImageThatDoesNotExist() {
        $this->adapter->deleteImage('publickey', 'id');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testLoadImageThatDoesNotExist() {
        $this->adapter->load('publickey', 'id', $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetLastModifiedOfImageThatDoesNotExist() {
        $this->adapter->getLastModified('publickey', 'id');
    }

    public function testGetLastModified() {
        $publicKey = 'publickey';
        $imageIdentifier = 'id';
        $image = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($publicKey, $imageIdentifier, $image));
        $this->assertInstanceOf('DateTime', $this->adapter->getLastModified($publicKey, $imageIdentifier));
    }

    public function testGetLastModifiedWhenUserHasNoImages() {
        $this->assertInstanceOf('DateTime', $this->adapter->getLastModified('publickey'));
    }

    public function testGetNumImages() {
        $publicKey = 'publickey';
        $image = $this->getImage();

        $this->assertSame(0, $this->adapter->getNumImages($publicKey));

        // Insert first image
        $this->assertTrue($this->adapter->insertImage($publicKey, 'id1', $image));
        $this->assertSame(1, $this->adapter->getNumImages($publicKey));

        // Insert same image
        $this->assertTrue($this->adapter->insertImage($publicKey, 'id1', $image));
        $this->assertSame(1, $this->adapter->getNumImages($publicKey));

        // Insert with a new ID
        $this->assertTrue($this->adapter->insertImage($publicKey, 'id2', $image));
        $this->assertSame(2, $this->adapter->getNumImages($publicKey));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetMetadataWhenImageDoesNotExist() {
        $this->adapter->getMetadata('publickey', 'id');
    }

    public function testGetMetadataWhenImageHasNone() {
        $publicKey = 'publickey';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($publicKey, $imageIdentifier, $this->getImage()));
        $this->assertSame(array(), $this->adapter->getMetadata($publicKey, $imageIdentifier));
    }

    public function testUpdateAndGetMetadata() {
        $publicKey = 'publickey';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($publicKey, $imageIdentifier, $this->getImage()));
        $this->assertTrue($this->adapter->updateMetadata($publicKey, $imageIdentifier, array('foo' => 'bar')));
        $this->assertSame(array('foo' => 'bar'), $this->adapter->getMetadata($publicKey, $imageIdentifier));
        $this->assertTrue($this->adapter->updateMetadata($publicKey, $imageIdentifier, array('foo' => 'foo', 'bar' => 'foo')));
        $this->assertSame(array('foo' => 'foo', 'bar' => 'foo'), $this->adapter->getMetadata($publicKey, $imageIdentifier));
    }

    public function testUpdateDeleteAndGetMetadata() {
        $publicKey = 'publickey';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($publicKey, $imageIdentifier, $this->getImage()));
        $this->assertTrue($this->adapter->updateMetadata($publicKey, $imageIdentifier, array('foo' => 'bar')));
        $this->assertSame(array('foo' => 'bar'), $this->adapter->getMetadata($publicKey, $imageIdentifier));
        $this->assertTrue($this->adapter->deleteMetadata($publicKey, $imageIdentifier));
        $this->assertSame(array(), $this->adapter->getMetadata($publicKey, $imageIdentifier));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testDeleteMetataFromImageThatDoesNotExist() {
        $this->adapter->deleteMetadata('publickey', 'id');
    }

    /**
     * Insert some images to test the query functionality
     *
     * All images added is owned by "publickey", unless $alternatePublicKey is set to true
     *
     * @param  boolean $alternatePublicKey Whether to alternate between 'publickey' and
     *                                     'publickey2' when inserting images
     * @return array Returns an array with two elements where the first is the timestamp of when
     *               the first image was added, and the second is the timestamp of when the last
     *               image was added
     */
    private function insertImages($alternatePublicKey = false) {
        $now = time();
        $start = $now;
        $images = array();

        foreach (array('image.jpg', 'image.png', 'image1.png', 'image2.png', 'image3.png', 'image4.png') as $i => $fileName) {
            $path = FIXTURES_DIR . '/' . $fileName;
            $info = getimagesize($path);

            $publicKey = 'publickey';
            if ($alternatePublicKey && $i % 2 === 0) {
                $publickey2 = 'publickey2';
            }

            $image = new Image();
            $image->setMimeType($info['mime'])
                  ->setExtension(substr($fileName, strrpos($fileName, '.') + 1))
                  ->setWidth($info[0])
                  ->setHeight($info[1])
                  ->setBlob(file_get_contents($path))
                  ->setAddedDate(new DateTime('@' . $now++, new DateTimeZone('UTC')))
                  ->setOriginalChecksum(md5_file($path));

            $imageIdentifier = md5($image->getBlob());

            // Add the image
            $this->adapter->insertImage($publicKey, $imageIdentifier, $image);

            // Insert some metadata
            $this->adapter->updateMetadata($publicKey, $imageIdentifier, array(
                'key' . $i => 'value' . $i,
            ));
        }

        // Remove the last increment to get the timestamp for when the last image was added
        $end = $now - 1;

        return array($start, $end);
    }

    public function testGetImagesWithNoQuery() {
        list($start, $end) = $this->insertImages();

        // Empty query
        $query = new Query();
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('setHits')->with(6);
        $images = $this->adapter->getImages('publickey', $query, $model);
        $this->assertCount(6, $images);
    }

    public function testGetImagesWithStartAndEndTimestamps() {
        list($start, $end) = $this->insertImages();

        $model = new Images();
        $publicKey = 'publickey';

        // Fetch to the timestamp of when the last image was added
        $query = new Query();
        $query->to($end);
        $this->assertCount(6, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(6, $model->getHits());

        // Fetch until the second the first image was added
        $query = new Query();
        $query->to($start);
        $this->assertCount(1, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(1, $model->getHits());

        // Fetch from the second the first image was added
        $query = new Query();
        $query->from($start);
        $this->assertCount(6, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(6, $model->getHits());

        // Fetch from the second the last image was added
        $query = new Query();
        $query->from($end);
        $this->assertCount(1, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(1, $model->getHits());
    }

    public function testGetImagesAndReturnMetadata() {
        $this->insertImages();

        $query = new Query();
        $query->returnMetadata(true);

        $images = $this->adapter->getImages('publickey', $query, $this->getMock('Imbo\Model\Images'));

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

    public function testGetImagesReturnsImagesOnlyForSpecifiedPublicKeys() {
        $this->insertImages(true);

        $model = new Images();
        $query = new Query();
        $images = $this->adapter->getImages('publickey', $query, $model);

        foreach ($images as $image) {
            $this->assertSame('publickey', $image['publicKey']);
        }

        $this->assertSame(count($images), $model->getHits());
    }

    public function testGetImagesReturnsImagesWithDateTimeInstances() {
        $this->insertImages();

        $images = $this->adapter->getImages('publickey', new Query(), $this->getMock('Imbo\Model\Images'));

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

        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('setHits')->with(6);

        $images = $this->adapter->getImages('publickey', $query, $model);
        $this->assertCount(count($imageIdentifiers), $images);

        foreach ($images as $i => $image) {
            $this->assertSame($imageIdentifiers[$i], $image['imageIdentifier']);
        }
    }

    public function testGetImageMimeType() {
        $images = array();
        $publicKey = 'publickey';

        $images[0] = new Image();
        $images[0]->setMimeType('image/png')
                  ->setExtension('png')
                  ->setWidth(665)
                  ->setHeight(463)
                  ->setBlob(file_get_contents(FIXTURES_DIR . '/image.png'))
                  ->setOriginalChecksum(md5_file(FIXTURES_DIR . '/image.png'));

        $images[1] = new Image();
        $images[1]->setMimeType('image/jpeg')
                  ->setExtension('jpg')
                  ->setWidth(665)
                  ->setHeight(463)
                  ->setBlob(file_get_contents(FIXTURES_DIR . '/image.jpg'))
                  ->setOriginalChecksum(md5_file(FIXTURES_DIR . '/image.jpg'));

        foreach ($images as $image) {
            $imageIdentifier = md5($image->getBlob());

            $this->adapter->insertImage($publicKey, $imageIdentifier, $image);
        }

        $this->assertSame('image/png', $this->adapter->getImageMimeType($publicKey, md5($images[0]->getBlob())));
        $this->assertSame('image/jpeg', $this->adapter->getImageMimeType($publicKey, md5($images[1]->getBlob())));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetMimeTypeWhenImageDoesNotExist() {
        $this->adapter->getImageMimeType('publickey', 'id');
    }

    public function testCanCheckIfImageAlreadyExists() {
        $publicKey = 'publickey';
        $imageIdentifier = 'id';

        $this->assertFalse($this->adapter->imageExists($publicKey, $imageIdentifier));
        $this->adapter->insertImage($publicKey, $imageIdentifier, $this->getImage());
        $this->assertTrue($this->adapter->imageExists($publicKey, $imageIdentifier));
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
        $publicKey = 'publickey';
        $imageIdentifier = 'id';
        $this->assertTrue($this->adapter->insertShortUrl($shortUrlId, $publicKey, $imageIdentifier, $extension, $query));

        $params = $this->adapter->getShortUrlParams($shortUrlId);

        $this->assertSame($publicKey, $params['publicKey']);
        $this->assertSame($imageIdentifier, $params['imageIdentifier']);
        $this->assertSame($extension, $params['extension']);
        $this->assertSame($query, $params['query']);

        $this->assertSame($shortUrlId, $this->adapter->getShortUrlId($publicKey, $imageIdentifier, $extension, $query));
    }

    public function testCanDeleteShortUrls() {
        $shortUrlId = 'aaaaaaa';
        $publicKey = 'publickey';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertShortUrl($shortUrlId, $publicKey, $imageIdentifier));
        $this->assertTrue($this->adapter->deleteShortUrls($publicKey, $imageIdentifier));
        $this->assertNull($this->adapter->getShortUrlParams($shortUrlId));
    }

    public function testCanDeleteASingleShortUrl() {
        $publicKey = 'publickey';
        $imageIdentifier = 'id';
        $this->assertTrue($this->adapter->insertShortUrl('aaaaaaa', $publicKey, $imageIdentifier));
        $this->assertTrue($this->adapter->insertShortUrl('bbbbbbb', $publicKey, $imageIdentifier));
        $this->assertTrue($this->adapter->insertShortUrl('ccccccc', $publicKey, $imageIdentifier));

        $this->assertTrue($this->adapter->deleteShortUrls($publicKey, $imageIdentifier, 'aaaaaaa'));
        $this->assertNull($this->adapter->getShortUrlParams('aaaaaaa'));
        $this->assertNotNull($this->adapter->getShortUrlParams('bbbbbbb'));
        $this->assertNotNull($this->adapter->getShortUrlParams('ccccccc'));
    }

    public function testCanFilterOnImageIdentifiers() {
        $publicKey = 'christer';
        $id1 = 'id1';
        $id2 = 'id2';
        $id3 = 'id3';
        $id4 = 'id4';
        $id5 = 'id5';
        $image = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($publicKey, $id1, $image));
        $this->assertTrue($this->adapter->insertImage($publicKey, $id2, $image));
        $this->assertTrue($this->adapter->insertImage($publicKey, $id3, $image));
        $this->assertTrue($this->adapter->insertImage($publicKey, $id4, $image));
        $this->assertTrue($this->adapter->insertImage($publicKey, $id5, $image));

        $query = new Query();
        $model = new Images();

        $query->imageIdentifiers(array($id1));
        $this->assertCount(1, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(1, $model->getHits());

        $query->imageIdentifiers(array($id1, $id2));
        $this->assertCount(2, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(2, $model->getHits());

        $query->imageIdentifiers(array($id1, $id2, $id3));
        $this->assertCount(3, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(3, $model->getHits());

        $query->imageIdentifiers(array($id1, $id2, $id3, $id4));
        $this->assertCount(4, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(4, $model->getHits());

        $query->imageIdentifiers(array($id1, $id2, $id3, $id4, $id5));
        $this->assertCount(5, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(5, $model->getHits());

        $query->imageIdentifiers(array($id1, $id2, $id3, $id4, $id5, str_repeat('f', 32)));
        $this->assertCount(5, $this->adapter->getImages($publicKey, $query, $model));
        $this->assertSame(5, $model->getHits());
    }

    public function testCanGetNumberOfBytes() {
        $this->adapter->insertImage('publickey', 'id', $this->getImage());
        $this->assertSame($this->getImage()->getFilesize(), $this->adapter->getNumBytes('publickey'));
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
                array('size'),
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
                array('size:desc'),
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
                array('width:asc', 'size:desc'),
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
    public function testCanSortImages(array $sort = null, $field, array $values) {
        $this->insertImages();

        $query = new Query();

        if ($sort !== null) {
            $query->sort($sort);
        }

        $images = $this->adapter->getImages('publickey', $query, $this->getMock('Imbo\Model\Images'));

        foreach ($images as $i => $image) {
            $this->assertSame($values[$i], $image[$field]);
        }
    }
}
