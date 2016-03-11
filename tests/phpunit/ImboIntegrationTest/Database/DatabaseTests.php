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
        $user = 'user';
        $imageIdentifier = 'id';
        $originalImage = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $originalImage));

        $image = new Image();
        $this->assertTrue($this->adapter->load($user, $imageIdentifier, $image));

        $this->assertSame($originalImage->getWidth(), $image->getWidth());
        $this->assertSame($originalImage->getHeight(), $image->getHeight());
        $this->assertSame($originalImage->getMimeType(), $image->getMimeType());
        $this->assertSame($originalImage->getFilesize(), $image->getFilesize());
        $this->assertSame($originalImage->getExtension(), $image->getExtension());
    }

    public function testStoreSameImageTwice() {
        $user = 'user';
        $imageIdentifier = 'id';
        $image = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $image));
        $lastModified1 = $this->adapter->getLastModified([$user], $imageIdentifier);

        sleep(1);

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $image));
        $lastModified2 = $this->adapter->getLastModified([$user], $imageIdentifier);

        $this->assertTrue($lastModified2 > $lastModified1);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testStoreDeleteAndGetImage() {
        $user = 'key';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $this->getImage()));
        $this->assertTrue($this->adapter->deleteImage($user, $imageIdentifier));

        $this->adapter->load($user, $imageIdentifier, $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testDeleteImageThatDoesNotExist() {
        $this->adapter->deleteImage('user', 'id');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testLoadImageThatDoesNotExist() {
        $this->adapter->load('user', 'id', $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetLastModifiedOfImageThatDoesNotExist() {
        $this->adapter->getLastModified(['user'], 'id');
    }

    public function testGetLastModified() {
        $user = 'user';
        $imageIdentifier = 'id';
        $image = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $image));
        $this->assertInstanceOf('DateTime', $this->adapter->getLastModified([$user], $imageIdentifier));
    }

    public function testGetLastModifiedWhenUserHasNoImages() {
        $this->assertInstanceOf('DateTime', $this->adapter->getLastModified(['user']));
    }

    public function testGetNumImages() {
        $user = 'user';
        $image = $this->getImage();

        $this->assertSame(0, $this->adapter->getNumImages($user));

        // Insert on a different user
        $this->assertTrue($this->adapter->insertImage('user2', 'id0', $image));
        $this->assertSame(1, $this->adapter->getNumImages());

        // Insert first image
        $this->assertTrue($this->adapter->insertImage($user, 'id1', $image));
        $this->assertSame(1, $this->adapter->getNumImages($user));

        // Insert same image
        $this->assertTrue($this->adapter->insertImage($user, 'id1', $image));
        $this->assertSame(1, $this->adapter->getNumImages($user));

        // Insert with a new ID
        $this->assertTrue($this->adapter->insertImage($user, 'id2', $image));
        $this->assertSame(2, $this->adapter->getNumImages($user));

        // Count total images, regardless of user
        $this->assertSame(3, $this->adapter->getNumImages());
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetMetadataWhenImageDoesNotExist() {
        $this->adapter->getMetadata('user', 'id');
    }

    public function testGetMetadataWhenImageHasNone() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $this->getImage()));
        $this->assertSame([], $this->adapter->getMetadata($user, $imageIdentifier));
    }

    public function testUpdateAndGetMetadata() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $this->getImage()));
        $this->assertTrue($this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $this->adapter->getMetadata($user, $imageIdentifier));
        $this->assertTrue($this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'foo', 'bar' => 'foo']));
        $this->assertSame(['foo' => 'foo', 'bar' => 'foo'], $this->adapter->getMetadata($user, $imageIdentifier));
    }

    public function testMetadataWithNestedArraysIsRepresetedCorrectly() {
        if (get_class($this->adapter) === 'Imbo\Database\Doctrine') {
            $this->markTestSkipped('Skipped for the Doctrine adapter as Doctrine can\'t handle types');
        }

        $metadata = [
            'string' => 'bar',
            'integer' => 1,
            'float' => 1.1,
            'boolean' => true,
            'list' => [1, 2, 3],
            'assoc' => [
                'string' => 'bar',
                'integer' => 1,
                'float' => 1.1,
                'boolean' => false,
                'list' => [1, 2, 3],
                'assoc' => [
                    'list' => [
                        1,
                        2, [
                            'list' => [1, 2, 3]
                        ],
                        [1, 2, 3],
                    ],
                ],
            ],
        ];

        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $this->getImage()));
        $this->assertTrue($this->adapter->updateMetadata($user, $imageIdentifier, $metadata));

        $this->assertSame($metadata, $this->adapter->getMetadata($user, $imageIdentifier));
    }

    public function testMetadataWithNestedArraysIsRepresetedCorrectlyWhenFetchingMultipleImages() {
        if (get_class($this->adapter) === 'Imbo\Database\Doctrine') {
            $this->markTestSkipped('Skipped for the Doctrine adapter as Doctrine can\'t handle types');
        }

        $metadata = [
            'string' => 'bar',
            'integer' => 1,
            'float' => 1.1,
            'boolean' => true,
            'list' => [1, 2, 3],
            'assoc' => [
                'string' => 'bar',
                'integer' => 1,
                'float' => 1.1,
                'boolean' => false,
                'list' => [1, 2, 3],
                'assoc' => [
                    'list' => [
                        1,
                        2, [
                            'list' => [1, 2, 3]
                        ],
                        [1, 2, 3],
                    ],
                ],
            ],
        ];

        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $this->getImage()));
        $this->assertTrue($this->adapter->updateMetadata($user, $imageIdentifier, $metadata));

        $query = new Query();
        $query->returnMetadata(true);

        $images = $this->adapter->getImages(['user'], $query, new Images());

        $this->assertCount(1, $images);

        $this->assertSame($metadata, $images[0]['metadata']);
    }

    public function testUpdateDeleteAndGetMetadata() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertImage($user, $imageIdentifier, $this->getImage()));
        $this->assertTrue($this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $this->adapter->getMetadata($user, $imageIdentifier));
        $this->assertTrue($this->adapter->deleteMetadata($user, $imageIdentifier));
        $this->assertSame([], $this->adapter->getMetadata($user, $imageIdentifier));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testDeleteMetataFromImageThatDoesNotExist() {
        $this->adapter->deleteMetadata('user', 'id');
    }

    /**
     * Insert some images to test the query functionality
     *
     * All images added is owned by "user", unless $alternateUser is set to true
     *
     * @param  boolean $alternateUser Whether to alternate between 'user' and 'user2' when
     *                                inserting images
     * @return array Returns an array with two elements where the first is the timestamp of when
     *               the first image was added, and the second is the timestamp of when the last
     *               image was added
     */
    private function insertImages($alternateUser = false) {
        $now = time();
        $start = $now;
        $images = [];

        foreach (['image.jpg', 'image.png', 'image1.png', 'image2.png', 'image3.png', 'image4.png'] as $i => $fileName) {
            $path = FIXTURES_DIR . '/' . $fileName;
            $info = getimagesize($path);

            $user = 'user';
            if ($alternateUser && $i % 2 === 0) {
                $user = 'user2';
            }

            $image = new Image();
            $image->setMimeType($info['mime'])
                  ->setExtension(substr($fileName, strrpos($fileName, '.') + 1))
                  ->setWidth($info[0])
                  ->setHeight($info[1])
                  ->setBlob(file_get_contents($path))
                  ->setAddedDate(new DateTime('@' . $now, new DateTimeZone('UTC')))
                  ->setUpdatedDate(new DateTime('@' . $now, new DateTimeZone('UTC')))
                  ->setOriginalChecksum(md5_file($path));

            $now++;

            $imageIdentifier = md5($image->getBlob());

            // Add the image
            $this->adapter->insertImage($user, $imageIdentifier, $image);

            // Insert some metadata
            $this->adapter->updateMetadata($user, $imageIdentifier, [
                'key' . $i => 'value' . $i,
            ]);
        }

        // Remove the last increment to get the timestamp for when the last image was added
        $end = $now - 1;

        return [$start, $end];
    }

    public function testGetImagesWithNoQuery() {
        list($start, $end) = $this->insertImages();

        // Empty query
        $query = new Query();
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('setHits')->with(6);
        $images = $this->adapter->getImages(['user'], $query, $model);
        $this->assertCount(6, $images);
    }

    public function testGetImagesWithStartAndEndTimestamps() {
        list($start, $end) = $this->insertImages();

        $model = new Images();
        $user = 'user';

        // Fetch to the timestamp of when the last image was added
        $query = new Query();
        $query->to($end);
        $this->assertCount(6, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(6, $model->getHits());

        // Fetch until the second the first image was added
        $query = new Query();
        $query->to($start);
        $this->assertCount(1, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(1, $model->getHits());

        // Fetch from the second the first image was added
        $query = new Query();
        $query->from($start);
        $this->assertCount(6, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(6, $model->getHits());

        // Fetch from the second the last image was added
        $query = new Query();
        $query->from($end);
        $this->assertCount(1, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(1, $model->getHits());
    }

    public function testGetImagesAndReturnMetadata() {
        $this->insertImages();

        $query = new Query();
        $query->returnMetadata(true);

        $images = $this->adapter->getImages(['user'], $query, $this->getMock('Imbo\Model\Images'));

        foreach ($images as $image) {
            $this->assertArrayHasKey('metadata', $image);
        }

        $this->assertSame(['key5' => 'value5'], $images[0]['metadata']);
        $this->assertSame(['key4' => 'value4'], $images[1]['metadata']);
        $this->assertSame(['key3' => 'value3'], $images[2]['metadata']);
        $this->assertSame(['key2' => 'value2'], $images[3]['metadata']);
        $this->assertSame(['key1' => 'value1'], $images[4]['metadata']);
        $this->assertSame(['key0' => 'value0'], $images[5]['metadata']);

    }

    public function testGetImagesReturnsImagesOnlyForSpecifiedUsers() {
        $this->insertImages(true);

        $model = new Images();
        $query = new Query();
        $images = $this->adapter->getImages(['user'], $query, $model);

        foreach ($images as $image) {
            $this->assertSame('user', $image['user']);
        }

        $this->assertSame(count($images), $model->getHits());
    }

    public function testGetImagesReturnsImagesWithDateTimeInstances() {
        $this->insertImages();

        $images = $this->adapter->getImages(['user'], new Query(), $this->getMock('Imbo\Model\Images'));

        foreach (['added', 'updated'] as $dateField) {
            foreach ($images as $image) {
                $this->assertInstanceOf('DateTime', $image[$dateField]);
            }
        }
    }

    public function getPageAndLimit() {
        return [
            'no page or limit' => [null, null, [
                'a501051db16e3cbf88ea50bfb0138a47',
                '1d5b88aec8a3e1c4c57071307b2dae3a',
                'b914b28f4d5faa516e2049b9a6a2577c',
                'fc7d2d06993047a0b5056e8fac4462a2',
                '929db9c5fc3099f7576f5655207eba47',
                'f3210f1bb34bfbfa432cc3560be40761',
            ]],
            'no page, 2 images' => [null, 2, [
                'a501051db16e3cbf88ea50bfb0138a47',
                '1d5b88aec8a3e1c4c57071307b2dae3a',
            ]],
            'first page, 2 images' => [1, 2, [
                'a501051db16e3cbf88ea50bfb0138a47',
                '1d5b88aec8a3e1c4c57071307b2dae3a',
            ]],
            'second page, 2 images' => [2, 2, [
                'b914b28f4d5faa516e2049b9a6a2577c',
                'fc7d2d06993047a0b5056e8fac4462a2',
            ]],
            'second page, 4 images' => [2, 4, [
                '929db9c5fc3099f7576f5655207eba47',
                'f3210f1bb34bfbfa432cc3560be40761',
            ]],
            'fourth page, 2 images' => [4, 2, []],
        ];
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

        $images = $this->adapter->getImages(['user'], $query, $model);
        $this->assertCount(count($imageIdentifiers), $images);

        foreach ($images as $i => $image) {
            $this->assertSame($imageIdentifiers[$i], $image['imageIdentifier']);
        }
    }

    public function testGetImageMimeType() {
        $images = [];
        $user = 'user';

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

            $this->adapter->insertImage($user, $imageIdentifier, $image);
        }

        $this->assertSame('image/png', $this->adapter->getImageMimeType($user, md5($images[0]->getBlob())));
        $this->assertSame('image/jpeg', $this->adapter->getImageMimeType($user, md5($images[1]->getBlob())));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetMimeTypeWhenImageDoesNotExist() {
        $this->adapter->getImageMimeType('user', 'id');
    }

    public function testCanCheckIfImageAlreadyExists() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertFalse($this->adapter->imageExists($user, $imageIdentifier));
        $this->adapter->insertImage($user, $imageIdentifier, $this->getImage());
        $this->assertTrue($this->adapter->imageExists($user, $imageIdentifier));
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getShortUrlVariations() {
        return [
            'without query and extension' => [
                'aaaaaaa',
            ],
            'with query and extension' => [
                'bbbbbbb',
                [
                    't' => [
                        'thumbnail:width=40'
                    ],
                    'accessToken' => 'token',
                ],
                'png',
            ],
            'with query' => [
                'ccccccc',
                [
                    't' => [
                        'thumbnail:width=40'
                    ],
                    'accessToken' => 'token',
                ],
            ],
            'with extension' => [
                'ddddddd',
                [],
                'gif',
            ],
        ];
    }

    /**
     * @dataProvider getShortUrlVariations
     */
    public function testCanInsertAndGetParametersForAShortUrl($shortUrlId, array $query = [], $extension = null) {
        $user = 'user';
        $imageIdentifier = 'id';
        $this->assertTrue($this->adapter->insertShortUrl($shortUrlId, $user, $imageIdentifier, $extension, $query));

        $params = $this->adapter->getShortUrlParams($shortUrlId);

        $this->assertSame($user, $params['user']);
        $this->assertSame($imageIdentifier, $params['imageIdentifier']);
        $this->assertSame($extension, $params['extension']);
        $this->assertSame($query, $params['query']);

        $this->assertSame($shortUrlId, $this->adapter->getShortUrlId($user, $imageIdentifier, $extension, $query));
    }

    public function testCanGetShortUrlIdThatDoesNotExist() {
        $this->assertNull($this->adapter->getShortUrlId('user', 'image'));
    }

    public function testCanDeleteShortUrls() {
        $shortUrlId = 'aaaaaaa';
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue($this->adapter->insertShortUrl($shortUrlId, $user, $imageIdentifier));
        $this->assertTrue($this->adapter->deleteShortUrls($user, $imageIdentifier));
        $this->assertNull($this->adapter->getShortUrlParams($shortUrlId));
    }

    public function testCanDeleteASingleShortUrl() {
        $user = 'user';
        $imageIdentifier = 'id';
        $this->assertTrue($this->adapter->insertShortUrl('aaaaaaa', $user, $imageIdentifier));
        $this->assertTrue($this->adapter->insertShortUrl('bbbbbbb', $user, $imageIdentifier));
        $this->assertTrue($this->adapter->insertShortUrl('ccccccc', $user, $imageIdentifier));

        $this->assertTrue($this->adapter->deleteShortUrls($user, $imageIdentifier, 'aaaaaaa'));
        $this->assertNull($this->adapter->getShortUrlParams('aaaaaaa'));
        $this->assertNotNull($this->adapter->getShortUrlParams('bbbbbbb'));
        $this->assertNotNull($this->adapter->getShortUrlParams('ccccccc'));
    }

    public function testCanFilterOnImageIdentifiers() {
        $user = 'christer';
        $id1 = 'id1';
        $id2 = 'id2';
        $id3 = 'id3';
        $id4 = 'id4';
        $id5 = 'id5';
        $image = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($user, $id1, $image));
        $this->assertTrue($this->adapter->insertImage($user, $id2, $image));
        $this->assertTrue($this->adapter->insertImage($user, $id3, $image));
        $this->assertTrue($this->adapter->insertImage($user, $id4, $image));
        $this->assertTrue($this->adapter->insertImage($user, $id5, $image));

        $query = new Query();
        $model = new Images();

        $query->imageIdentifiers([$id1]);
        $this->assertCount(1, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(1, $model->getHits());

        $query->imageIdentifiers([$id1, $id2]);
        $this->assertCount(2, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(2, $model->getHits());

        $query->imageIdentifiers([$id1, $id2, $id3]);
        $this->assertCount(3, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(3, $model->getHits());

        $query->imageIdentifiers([$id1, $id2, $id3, $id4]);
        $this->assertCount(4, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(4, $model->getHits());

        $query->imageIdentifiers([$id1, $id2, $id3, $id4, $id5]);
        $this->assertCount(5, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(5, $model->getHits());

        $query->imageIdentifiers([$id1, $id2, $id3, $id4, $id5, str_repeat('f', 32)]);
        $this->assertCount(5, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(5, $model->getHits());
    }

    public function testCanFilterOnChecksums() {
        $user = 'christer';
        $id1 = 'id1';
        $id2 = 'id2';
        $id3 = 'id3';
        $id4 = 'id4';
        $id5 = 'id5';
        $image1 = $this->getImage();
        $image1->setChecksum('checksum1');
        $image2 = $this->getImage();
        $image2->setChecksum('checksum2');
        $image3 = $this->getImage();
        $image3->setChecksum('checksum3');
        $image4 = $this->getImage();
        $image4->setChecksum('checksum4');
        $image5 = $this->getImage();
        $image5->setChecksum('checksum5');

        // This is the same for all image objects above
        $originalChecksum = $image1->getOriginalChecksum();

        $this->assertTrue($this->adapter->insertImage($user, $id1, $image1));
        $this->assertTrue($this->adapter->insertImage($user, $id2, $image2));
        $this->assertTrue($this->adapter->insertImage($user, $id3, $image3));
        $this->assertTrue($this->adapter->insertImage($user, $id4, $image4));
        $this->assertTrue($this->adapter->insertImage($user, $id5, $image5));

        $query = new Query();
        $model = new Images();

        $query->originalChecksums(['foobar']);
        $this->assertCount(0, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(0, $model->getHits());

        $query->originalChecksums([$originalChecksum]);
        $this->assertCount(5, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(5, $model->getHits());

        $query->checksums(['foobar']);
        $this->assertCount(0, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(0, $model->getHits());

        $query->checksums(['checksum1']);
        $this->assertCount(1, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(1, $model->getHits());

        $query->checksums(['checksum1', 'checksum2']);
        $this->assertCount(2, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(2, $model->getHits());

        $query->checksums(['checksum1', 'checksum2', 'checksum3']);
        $this->assertCount(3, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(3, $model->getHits());

        $query->checksums(['checksum1', 'checksum2', 'checksum3', 'checksum4']);
        $this->assertCount(4, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(4, $model->getHits());

        $query->checksums(['checksum1', 'checksum2', 'checksum3', 'checksum4', 'checksum5']);
        $this->assertCount(5, $this->adapter->getImages([$user], $query, $model));
        $this->assertSame(5, $model->getHits());
    }

    public function testCanGetNumberOfBytes() {
        $this->assertSame(0, $this->adapter->getNumBytes('user'));

        $this->adapter->insertImage('user', 'id', $this->getImage());
        $this->assertSame($this->getImage()->getFilesize(), $this->adapter->getNumBytes('user'));

        $this->adapter->insertImage('user2', 'id', $this->getImage());
        $this->assertSame($this->getImage()->getFilesize(), $this->adapter->getNumBytes('user2'));

        $this->assertSame($this->getImage()->getFilesize() * 2, $this->adapter->getNumBytes());
    }

    public function testCanGetNumberOfUsers() {
        $this->adapter->insertImage('user', 'id', $this->getImage());
        $this->assertSame(1, $this->adapter->getNumUsers());

        $this->adapter->insertImage('user2', 'id', $this->getImage());
        $this->assertSame(2, $this->adapter->getNumUsers());
    }

    public function getSortData() {
        return [
            'no sorting' => [
                null,
                'imageIdentifier',
                [
                    'a501051db16e3cbf88ea50bfb0138a47',
                    '1d5b88aec8a3e1c4c57071307b2dae3a',
                    'b914b28f4d5faa516e2049b9a6a2577c',
                    'fc7d2d06993047a0b5056e8fac4462a2',
                    '929db9c5fc3099f7576f5655207eba47',
                    'f3210f1bb34bfbfa432cc3560be40761',
                ],
            ],
            'default sort on size' => [
                ['size'],
                'size',
                [
                    41423,
                    64828,
                    74337,
                    84988,
                    92795,
                    95576,
                ],
            ],
            'desc sort on size' => [
                ['size:desc'],
                'size',
                [
                    95576,
                    92795,
                    84988,
                    74337,
                    64828,
                    41423,
                ],
            ],
            'sort on multiple fields' => [
                ['width:asc', 'size:desc'],
                'size',
                [
                    74337,
                    84988,
                    92795,
                    95576,
                    64828,
                    41423,
                ],
            ],
        ];
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

        $images = $this->adapter->getImages(['user'], $query, $this->getMock('Imbo\Model\Images'));

        foreach ($images as $i => $image) {
            $this->assertSame($values[$i], $image[$field]);
        }
    }

    public function testCanGetStatus() {
        $this->assertTrue($this->adapter->getStatus());
    }
}
