<?php
namespace ImboIntegrationTest\Database;

use Imbo\Model\Image;
use Imbo\Model\Images;
use Imbo\Resource\Images\Query;
use Imbo\Database\Doctrine;
use Imbo\Exception\DuplicateImageIdentifierException;
use Imbo\Exception\DatabaseException;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTests extends TestCase {
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
     * Insert an image into the database
     *
     * @param array $image An image array with the following keys / types:
     *                     - (int) id
     *                     - (string) user
     *                     - (string) imageIdentifier
     *                     - (int) size
     *                     - (string) extension
     *                     - (string) mime
     *                     - (int) added
     *                     - (int) updated
     *                     - (int) width
     *                     - (int) height
     *                     - (string) checksum
     *                     - (string) originalChecksum
     */
    abstract protected function insertImage(array $image);

    /**
     * Set up
     */
    public function setUp() : void {
        $this->adapter = $this->getAdapter();
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

    /**
     * @covers ::insertImage
     * @covers ::load
     */
    public function testCanInsertAndGetImage() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $this->getImage()),
            'Could not insert image'
        );

        $image = new Image();
        $this->assertTrue(
            $this->adapter->load($user, $imageIdentifier, $image),
            'Could not load image'
        );

        $this->assertSame(123, $image->getWidth(), 'Image width is incorrect');
        $this->assertSame(234, $image->getHeight(), 'Image height is incorrect');
        $this->assertSame('image/jpeg', $image->getMimeType(), 'Image mime type is incorrect');
        $this->assertSame(3456, $image->getFilesize(), 'Image filesize is incorrect');
        $this->assertSame('jpg', $image->getExtension(), 'Image extension is incorrect');
    }

    /**
     * @covers ::insertImage
     * @covers ::getLastModified
     */
    public function testWillStoreSameImageTwiceWithUpdateIfDuplicate() {
        $user = 'user';
        $imageIdentifier = 'id';
        $image = $this->getImage();

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $image),
            'Could not insert image'
        );
        $lastModified1 = $this->adapter->getLastModified([$user], $imageIdentifier);

        sleep(1);

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $image),
            'Could not insert image a second time'
        );
        $lastModified2 = $this->adapter->getLastModified([$user], $imageIdentifier);

        $this->assertTrue(
            $lastModified2 > $lastModified1,
            'Second last modification date is not greater than the first'
        );
    }

    /**
     * @covers ::insertImage
     */
    public function testStoreSameImageTwiceWithoutUpdateIfDuplicate() {
        $user = 'user';
        $imageIdentifier = 'id';
        $image = $this->getImage();

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $image, false),
            'Could not insert image'
        );

        sleep(1);

        $this->expectExceptionObject(new DuplicateImageIdentifierException(
            'Duplicate image identifier when attempting to insert image into DB.',
            503
        ));

        $this->adapter->insertImage($user, $imageIdentifier, $image, false);
    }

    /**
     * @covers ::insertImage
     * @covers ::deleteImage
     * @covers ::load
     */
    public function testDeleteImages() {
        $user = 'key';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $this->getImage()),
            'Could not insert image'
        );
        $this->assertTrue(
            $this->adapter->deleteImage($user, $imageIdentifier),
            'Could not delete image'
        );

        $this->expectExceptionObject(new DatabaseException('Image not found', 404));

        $this->adapter->load($user, $imageIdentifier, $this->createMock('Imbo\Model\Image'));
    }

    /**
     * @covers ::deleteImage
     */
    public function testDeleteImageThatDoesNotExist() {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->deleteImage('user', 'id');
    }

    /**
     * @covers ::load
     */
    public function testLoadImageThatDoesNotExist() {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->load('user', 'id', $this->createMock('Imbo\Model\Image'));
    }

    /**
     * Get lists of users
     *
     * @return array[]
     */
    public function getUsers() {
        return [
            'no users' => [
                'users' => [],
            ],
            'multiple users' => [
                'users' => ['user1', 'user2', 'user3'],
            ],
        ];
    }

    /**
     * @dataProvider getUsers
     * @covers ::getLastModified
     * @param string[] $users
     */
    public function testGetLastModifiedOfImageThatDoesNotExist(array $users) {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->getLastModified($users, 'id');
    }

    /**
     * Get a DateTime instance given a Unix timestamp
     *
     * @param int $timestamp
     * @return DateTime
     */
    protected function getDateTime($timestamp) {
        return new DateTime(sprintf('@%d', $timestamp), new DateTimeZone('UTC'));
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForLastModificationTest() {
        // Default image
        $image = [
            'user' => 'user',
            'imageIdentifier' => uniqid(),
            'size' => 12505,
            'extension' => 'png',
            'mime' => 'image/png',
            'added' => 1499234238,
            'updated' => 1499234238,
            'width' => 1024,
            'height' => 256,
            'checksum' => 'b60df41830245ee8f278e3ddfe5238a3',
            'originalChecksum' => 'b60df41830245ee8f278e3ddfe5238a3',
        ];

        return [
            'empty database / users / no image identifier' => [
                'images' => [],
                'users' => [
                    'someuser',
                    'someotheruser'
                ],
                'imageIdentifier' => null,
                'compareDateTimeValue' => false,
            ],

            'images / users with no hit / no image identifier' => [
                'images' => [
                    $image,
                ],
                'users' => [
                    'someuser',
                ],
                'imageIdentifier' => null,
                'compareDateTimeValue' => false,
            ],

            'images / users with one hit / no image identifier' => [
                'images' => [
                    $image,
                ],
                'users' => [
                    'user',
                ],
                'imageIdentifier' => null,
                'compareDateTimeValue' => true,
                'expectedDateTime' => $this->getDateTime($image['updated']),
            ],

            'images / multiple users with hits / no image identifier' => [
                'images' => [
                    ['updated' => 123, 'user' => 'user1', 'imageIdentifier' => uniqid()] + $image,
                    ['updated' => 124, 'user' => 'user2', 'imageIdentifier' => uniqid()] + $image,
                    ['updated' => 127, 'user' => 'user2', 'imageIdentifier' => uniqid()] + $image,
                    ['updated' => 126, 'user' => 'user1', 'imageIdentifier' => uniqid()] + $image,
                    ['updated' => 124, 'user' => 'user2', 'imageIdentifier' => uniqid()] + $image,
                ],
                'users' => [
                    'user', 'user1', 'user2', 'user3',
                ],
                'imageIdentifier' => null,
                'compareDateTimeValue' => true,
                'expectedDateTime' => $this->getDateTime(127),
            ],
        ];
    }

    /**
     * @dataProvider getDataForLastModificationTest
     * @covers ::getLastModified
     * @param array $images Images to manually insert into the database
     * @param array $users Users for the query
     * @param string $imageIdentifier Image identifier for the query
     * @param boolean $compareDateTimeValue
     * @param DateTime $expectedDateTime
     */
    public function testCanGetLastModifiedDate(array $images, array $users, $imageIdentifier, $compareDateTimeValue, DateTime $expectedDateTime = null) {
        foreach ($images as $image) {
            $this->insertImage($image);
        }

        $lastModified = $this->adapter->getLastModified($users, $imageIdentifier);

        $this->assertInstanceOf(DateTime::class, $lastModified, 'Expected instance of DateTime');

        if ($compareDateTimeValue) {
            $this->assertSame(
                $expectedDateTime->format('r'),
                $lastModified->format('r'),
                'Incorrect DateTime value'
            );
        }
    }

    /**
     * @covers ::setLastModifiedNow
     */
    public function testCanSetLastModifiedDateToNow() {
        $user = 'user';
        $imageIdentifier = 'id';

        $original = $this->getImage();
        $added = time() - 10;
        $original->setAddedDate(new DateTime('@' . $added, new DateTimeZone('UTC')));
        $original->setUpdatedDate(new DateTime('@' . $added, new DateTimeZone('UTC')));

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $original),
            'Could not insert image'
        );

        $now = $this->adapter->setLastModifiedNow($user, $imageIdentifier);
        $this->assertEqualsWithDelta(time(), $now->getTimestamp(), 1, 'Returned timestamp should be around now');

        $image = new Image();
        $this->assertTrue($this->adapter->load($user, $imageIdentifier, $image));

        $this->assertEquals($added, $image->getAddedDate()->getTimestamp(), 'Added timestamp should not be modified');
        $this->assertEquals($now->getTimestamp(), $image->getUpdatedDate()->getTimestamp(), 'Updated timestamp should have updated');

        $lastModified = $this->adapter->getLastModified([$user], $imageIdentifier);
        $this->assertEquals($now->getTimestamp(), $lastModified->getTimestamp(), 'Last timestamp should have updated');
    }

    /**
     * @covers ::setLastModifiedTime
     */
    public function testCanSetLastModifiedDateToTimestamp() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $this->getImage()),
            'Could not insert image'
        );

        $desired = new DateTime('@' . (time() + 10), new DateTimeZone('UTC'));

        $returned = $this->adapter->setLastModifiedTime($user, $imageIdentifier, $desired);
        $this->assertEquals($desired->getTimestamp(), $returned->getTimestamp(), 'Returned timestamp should be around now');

        $image = new Image();
        $this->assertTrue($this->adapter->load($user, $imageIdentifier, $image));

        $this->assertEquals($desired->getTimestamp(), $image->getUpdatedDate()->getTimestamp(), 'Updated timestamp should have updated');

        $lastModified = $this->adapter->getLastModified([$user], $imageIdentifier);
        $this->assertEquals($desired->getTimestamp(), $lastModified->getTimestamp(), 'Last timestamp should have updated');
    }

    /**
     * @covers ::setLastModifiedTime
     */
    public function testCannotSetLastModifiedDateForMissingImage() {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Image not found');
        $this->adapter->setLastModifiedNow('user', 'id');
    }

    /**
     * @covers ::getNumImages
     */
    public function testGetNumImages() {
        $user = 'user';
        $image = $this->getImage();

        $this->assertSame(
            0,
            $num = $this->adapter->getNumImages($user),
            sprintf('"%s" was supposed to have 0 images, had %d', $user, $num)
        );

        // Insert on a different user
        $this->assertTrue(
            $this->adapter->insertImage('user2', 'id0', $image),
            'Could not insert image'
        );
        $this->assertSame(
            1,
            $num = $this->adapter->getNumImages(),
            sprintf('Expected 1 image, got %d', $num)
        );

        // Insert first image
        $this->assertTrue(
            $this->adapter->insertImage($user, 'id1', $image),
            'Could not insert image'
        );
        $this->assertSame(
            1,
            $num = $this->adapter->getNumImages($user),
            sprintf('"%s" was supposed to have 1 image, had %d', $user, $num)
        );

        // Insert same image
        $this->assertTrue(
            $this->adapter->insertImage($user, 'id1', $image),
            'Could not insert image'
        );
        $this->assertSame(
            1,
            $num = $this->adapter->getNumImages($user),
            sprintf('"%s" was supposed to have 1 image, had %d', $user, $num)
        );

        // Insert with a new ID
        $this->assertTrue(
            $this->adapter->insertImage($user, 'id2', $image),
            'Could not insert image'
        );
        $this->assertSame(
            2,
            $num = $this->adapter->getNumImages($user),
            sprintf('"%s" was supposed to have 2 images, had %d', $user, $num)
        );

        // Count total images, regardless of user
        $this->assertSame(
            3,
            $num = $this->adapter->getNumImages(),
            sprintf('Expected 3 images, got %d', $num)
        );
    }

    /**
     * @covers ::getMetadata
     */
    public function testGetMetadataWhenImageDoesNotExist() {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->getMetadata('user', 'id');
    }

    /**
     * @covers ::getMetadata
     */
    public function testGetMetadataWhenImageHasNone() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $this->getImage()),
            'Could not insert image'
        );
        $this->assertSame(
            [],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Expected metadata to be empty'
        );
    }

    /**
     * @covers ::getMetadata
     * @covers ::updateMetadata
     */
    public function testUpdateAndGetMetadata() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $this->getImage()),
            'Could not insert image'
        );
        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'bar']),
            'Could not update metadata'
        );
        $this->assertSame(
            ['foo' => 'bar'],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect'
        );
        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'foo', 'bar' => 'foo']),
            'Could not update metadata'
        );
        $this->assertSame(
            ['foo' => 'foo', 'bar' => 'foo'],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect'
        );
    }

    /**
     * @covers ::updateMetadata
     * @covers ::getMetadata
     */
    public function testMetadataWithNestedArraysIsRepresetedCorrectly() {
        $assertion = 'assertSame';

        if ($this->adapter instanceof Doctrine) {
            // Use a less struct assertion as the Doctrine adapter don't handle types
            $assertion = 'assertEquals';
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

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $this->getImage()),
            'Could not insert image'
        );
        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, $metadata),
            'Could not update metadata'
        );
        $this->$assertion(
            $metadata,
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect'
        );
    }

    /**
     * @covers ::updateMetadata
     * @covers ::getImages
     */
    public function testMetadataWithNestedArraysIsRepresetedCorrectlyWhenFetchingMultipleImages() {
        $assertion = 'assertSame';

        if ($this->adapter instanceof Doctrine) {
            // Use a less struct assertion as the Doctrine adapter don't handle types
            $assertion = 'assertEquals';
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

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $this->getImage()),
            'Could not insert image'
        );
        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, $metadata),
            'Could not update metadata'
        );

        $query = new Query();
        $query->returnMetadata(true);

        $images = $this->adapter->getImages(['user'], $query, new Images());

        $this->assertCount(1, $images, 'Expected array to have exactly one image');
        $this->$assertion($metadata, $images[0]['metadata'], 'Metadata is incorrect');
    }

    /**
     * @covers ::updateMetadata
     * @covers ::getMetadata
     * @covers ::deleteMetadata
     */
    public function testUpdateDeleteAndGetMetadata() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $this->getImage()),
            'Could not insert image'
        );
        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'bar']),
            'Could not update metadata'
        );
        $this->assertSame(
            ['foo' => 'bar'],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect'
        );
        $this->assertTrue(
            $this->adapter->deleteMetadata($user, $imageIdentifier),
            'Could not delete metadata'
        );
        $this->assertSame(
            [],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect'
        );
    }

    /**
     * @covers ::deleteMetadata
     */
    public function testDeleteMetataFromImageThatDoesNotExist() {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
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

    /**
     * @covers ::getImages
     */
    public function testGetImagesWithStartAndEndTimestamps() {
        $times = $this->insertImages();
        $start = $times[0];
        $end = $times[1];

        $model = new Images();
        $user = 'user';

        // Fetch to the timestamp of when the last image was added
        $query = new Query();
        $query->to($end);
        $this->assertCount(
            6,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 6 images, got %d', count($images))
        );
        $this->assertSame(
            6,
            $hits = $model->getHits(),
            sprintf('Incorrect hits value in model. Expected 6, got %d', $hits)
        );

        // Fetch until the second the first image was added
        $query = new Query();
        $query->to($start);
        $this->assertCount(
            1,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 1 image, got %d', count($images))
        );
        $this->assertSame(
            1,
            $hits = $model->getHits(),
            sprintf('Incorrect hits value in model. Expected 6, got %d', $hits)
        );

        // Fetch from the second the first image was added
        $query = new Query();
        $query->from($start);
        $this->assertCount(
            6,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 6 images, got %d', count($images))
        );
        $this->assertSame(
            6,
            $hits = $model->getHits(),
            sprintf('Incorrect hits value in model. Expected 6, got %d', $hits)
        );

        // Fetch from the second the last image was added
        $query = new Query();
        $query->from($end);
        $this->assertCount(
            1,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 1 image, got %d', count($images))
        );
        $this->assertSame(
            1,
            $hits = $model->getHits(),
            sprintf('Incorrect hits value in model. Expected 6, got %d', $hits)
        );
    }

    /**
     * @see https://github.com/imbo/imbo/pull/491
     * @covers ::getImages
     */
    public function testGetImagesAndReturnMetadata() {
        $this->insertImages(true);

        $query = new Query();
        $query->returnMetadata(true);

        $images = $this->adapter->getImages(['user', 'user2'], $query, $this->createMock('Imbo\Model\Images'));
        $this->assertCount(6, $images, sprintf('Incorrect length. Expected 6, got %d', count($images)));

        foreach ($images as $image) {
            $this->assertArrayHasKey('metadata', $image);
        }

        $this->assertSame('user',  $images[0]['user'], 'Incorrect user');
        $this->assertSame(['key5' => 'value5'], $images[0]['metadata'], 'Incorrect metadata');

        $this->assertSame('user2', $images[1]['user'], 'Incorrect user');
        $this->assertSame(['key4' => 'value4'], $images[1]['metadata'], 'Incorrect metadata');

        $this->assertSame('user',  $images[2]['user'], 'Incorrect user');
        $this->assertSame(['key3' => 'value3'], $images[2]['metadata'], 'Incorrect metadata');

        $this->assertSame('user2', $images[3]['user'], 'Incorrect user');
        $this->assertSame(['key2' => 'value2'], $images[3]['metadata'], 'Incorrect metadata');

        $this->assertSame('user',  $images[4]['user'], 'Incorrect user');
        $this->assertSame(['key1' => 'value1'], $images[4]['metadata'], 'Incorrect metadata');

        $this->assertSame('user2', $images[5]['user'], 'Incorrect user');
        $this->assertSame(['key0' => 'value0'], $images[5]['metadata'], 'Incorrect metadata');
    }

    /**
     * @covers ::getImages
     */
    public function testGetImagesReturnsImagesWithDateTimeInstances() {
        $this->insertImages();

        $images = $this->adapter->getImages(['user'], new Query(), $this->createMock('Imbo\Model\Images'));

        foreach (['added', 'updated'] as $dateField) {
            foreach ($images as $image) {
                $this->assertInstanceOf(DateTime::class, $image[$dateField], 'Incorrect date value');
            }
        }
    }

    /**
     * Get page and limit values for querying images
     *
     * @return array[]
     */
    public function getPageAndLimit() {
        return [
            'no page or limit' => [
                'page' => null,
                'limit' => null,
                'imageIdentifiers' => [
                    'a501051db16e3cbf88ea50bfb0138a47',
                    '1d5b88aec8a3e1c4c57071307b2dae3a',
                    'b914b28f4d5faa516e2049b9a6a2577c',
                    'fc7d2d06993047a0b5056e8fac4462a2',
                    '929db9c5fc3099f7576f5655207eba47',
                    'f3210f1bb34bfbfa432cc3560be40761',
                ],
            ],
            'no page, 2 images' => [
                'page' => null,
                'limit' => 2,
                'imageIdentifiers' => [
                    'a501051db16e3cbf88ea50bfb0138a47',
                    '1d5b88aec8a3e1c4c57071307b2dae3a',
                ],
            ],
            'first page, 2 images' => [
                'page' => 1,
                'limit' => 2,
                'imageIdentifiers' => [
                    'a501051db16e3cbf88ea50bfb0138a47',
                    '1d5b88aec8a3e1c4c57071307b2dae3a',
                ],
            ],
            'second page, 2 images' => [
                'page' => 2,
                'limit' => 2,
                'imageIdentifiers' => [
                    'b914b28f4d5faa516e2049b9a6a2577c',
                    'fc7d2d06993047a0b5056e8fac4462a2',
                ],
            ],
            'second page, 4 images' => [
                'page' => 2,
                'limit' => 4,
                'imageIdentifiers' => [
                    '929db9c5fc3099f7576f5655207eba47',
                    'f3210f1bb34bfbfa432cc3560be40761',
                ],
            ],
            'fourth page, 2 images' => [
                'page' => 4,
                'limit' => 2,
                'imageIdentifiers' => [],
            ],
        ];
    }

    /**
     * @dataProvider getPageAndLimit
     * @covers ::getImages
     * @param int|null $page
     * @param int|null $limit
     * @param array $imageIdentifiers
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

        $model = $this->createMock('Imbo\Model\Images');
        $model->expects($this->once())->method('setHits')->with(6);

        $images = $this->adapter->getImages(['user'], $query, $model);
        $this->assertCount(
            $num = count($imageIdentifiers),
            $images,
            sprintf('Expected %d images, got %d', $num, count($images))
        );

        foreach ($images as $i => $image) {
            $this->assertSame(
                $imageIdentifiers[$i],
                $image['imageIdentifier'],
                'Incorrect image identifier'
            );
        }
    }

    /**
     * @covers ::getImageMimeType
     */
    public function testGetImageMimeType() {
        $images = [];
        $user = 'user';

        $images[] = (new Image())->setMimeType('image/png')
                                 ->setExtension('png')
                                 ->setWidth(665)
                                 ->setHeight(463)
                                 ->setBlob(file_get_contents(FIXTURES_DIR . '/image.png'))
                                 ->setOriginalChecksum(md5_file(FIXTURES_DIR . '/image.png'));

        $images[] = (new Image())->setMimeType('image/jpeg')
                                 ->setExtension('jpg')
                                 ->setWidth(665)
                                 ->setHeight(463)
                                 ->setBlob(file_get_contents(FIXTURES_DIR . '/image.jpg'))
                                 ->setOriginalChecksum(md5_file(FIXTURES_DIR . '/image.jpg'));

        foreach ($images as $image) {
            $this->adapter->insertImage($user, md5($image->getBlob()), $image);
        }

        $this->assertSame(
            'image/png',
            $mimeType = $this->adapter->getImageMimeType($user, md5($images[0]->getBlob())),
            sprintf('Incorrect mime type. Expected image/png, got %s', $mimeType)
        );
        $this->assertSame(
            'image/jpeg',
            $mimeType = $this->adapter->getImageMimeType($user, md5($images[1]->getBlob())),
            sprintf('Incorrect mime type. Expected image/jpeg, got %s', $mimeType)
        );
    }

    /**
     * @covers ::getImageMimeType
     */
    public function testGetMimeTypeWhenImageDoesNotExist() {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->getImageMimeType('user', 'id');
    }

    /**
     * @covers ::imageExists
     */
    public function testCanCheckIfImageAlreadyExists() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertFalse(
            $this->adapter->imageExists($user, $imageIdentifier),
            'Image should not exist'
        );
        $this->adapter->insertImage($user, $imageIdentifier, $this->getImage());
        $this->assertTrue(
            $this->adapter->imageExists($user, $imageIdentifier),
            'Image should exist'
        );
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getShortUrlVariations() {
        return [
            'without query and extension' => [
                'shortUrlId' => 'aaaaaaa',
            ],
            'with query and extension' => [
                'shortUrlId' => 'bbbbbbb',
                'query' => [
                    't' => [
                        'thumbnail:width=40'
                    ],
                    'accessToken' => 'token',
                ],
                'extension' => 'png',
            ],
            'with query' => [
                'shortUrlId' => 'ccccccc',
                'query' => [
                    't' => [
                        'thumbnail:width=40'
                    ],
                    'accessToken' => 'token',
                ],
            ],
            'with extension' => [
                'shortUrlId' => 'ddddddd',
                'query' => [],
                'extension' => 'gif',
            ],
        ];
    }

    /**
     * @dataProvider getShortUrlVariations
     * @covers ::insertShortUrl
     * @covers ::getShortUrlParams
     * @covers ::getShortUrlId
     * @param string $shortUrlId
     * @param array $query
     * @param string $extension
     */
    public function testCanInsertAndGetParametersForAShortUrl($shortUrlId, array $query = [], $extension = null) {
        $user = 'user';
        $imageIdentifier = 'id';
        $this->assertTrue(
            $this->adapter->insertShortUrl($shortUrlId, $user, $imageIdentifier, $extension, $query),
            'Could not insert short URL'
        );

        $params = $this->adapter->getShortUrlParams($shortUrlId);

        $this->assertSame(
            $user,
            $params['user'],
            sprintf('Incorrect user. Expected "%s", got "%s"', $user, $params['user'])
        );
        $this->assertSame(
            $imageIdentifier,
            $params['imageIdentifier'],
            sprintf('Incorrect image identifier. Expected "%s", got "%s"', $imageIdentifier, $params['imageIdentifier'])
        );
        $this->assertSame(
            $extension,
            $params['extension'],
            sprintf('Incorrect extension. Expected "%s", got "%s"', $extension, $params['extension'])
        );
        $this->assertSame($query, $params['query'], 'Incorrect query');
        $this->assertSame(
            $shortUrlId,
            $id = $this->adapter->getShortUrlId($user, $imageIdentifier, $extension, $query),
            sprintf('Incorrect short URL ID. Expected "%s", got "%s"', $shortUrlId, $id)
        );
    }

    /**
     * @covers ::getShortUrlId
     */
    public function testCanGetShortUrlIdThatDoesNotExist() {
        $this->assertNull(
            $id = $this->adapter->getShortUrlId('user', 'image'),
            sprintf('Expected null, got "%s"', $id)
        );
    }

    /**
     * @covers ::insertShortUrl
     * @covers ::deleteShortUrls
     * @covers ::getShortUrlParams
     */
    public function testCanDeleteShortUrls() {
        $shortUrlId = 'aaaaaaa';
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertShortUrl($shortUrlId, $user, $imageIdentifier),
            'Could not insert short URL'
        );
        $this->assertTrue(
            $this->adapter->deleteShortUrls($user, $imageIdentifier),
            'Could not delete short URLs'
        );
        $this->assertNull(
            $this->adapter->getShortUrlParams($shortUrlId),
            'Did not expect to get short URL params'
        );
    }

    /**
     * @covers ::insertShortUrl
     * @covers ::deleteShortUrls
     * @covers ::getShortUrlParams
     */
    public function testCanDeleteASingleShortUrl() {
        $user = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertShortUrl('aaaaaaa', $user, $imageIdentifier),
            'Could not insert short URL'
        );
        $this->assertTrue(
            $this->adapter->insertShortUrl('bbbbbbb', $user, $imageIdentifier),
            'Could not insert short URL'
        );
        $this->assertTrue(
            $this->adapter->insertShortUrl('ccccccc', $user, $imageIdentifier),
            'Could not insert short URL'
        );
        $this->assertTrue(
            $this->adapter->deleteShortUrls($user, $imageIdentifier, 'aaaaaaa'),
            'Could not delete short URLs'
        );
        $this->assertNull(
            $this->adapter->getShortUrlParams('aaaaaaa'),
            'Did not expect to get short URL params'
        );
        $this->assertNotNull(
            $this->adapter->getShortUrlParams('bbbbbbb'),
            'Expected short URL params'
        );
        $this->assertNotNull(
            $this->adapter->getShortUrlParams('ccccccc'),
            'Expected short URL params'
        );
    }

    /**
     * @covers ::getImages
     */
    public function testCanFilterOnImageIdentifiers() {
        $user = 'user';
        $id1 = 'id1';
        $id2 = 'id2';
        $id3 = 'id3';
        $image = $this->getImage();

        $this->assertTrue($this->adapter->insertImage($user, $id1, $image), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user, $id2, $image), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user, $id3, $image), 'Could not insert image');

        $query = new Query();
        $model = new Images();

        $query->imageIdentifiers([$id1]);
        $this->assertCount(
            1,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 1 image, got %d', count($images))
        );
        $this->assertSame(
            1,
            $num = $model->getHits(),
            sprintf('Expected model to have 1 hit, got %d', $num)
        );

        $query->imageIdentifiers([$id1, $id2]);
        $this->assertCount(
            2,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 2 images, got %d', count($images))
        );
        $this->assertSame(
            2,
            $num = $model->getHits(),
            sprintf('Expected model to have 2 hits, got %d', $num)
        );

        $query->imageIdentifiers([$id1, $id2, $id3]);
        $this->assertCount(
            3,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 3 images, got %d', count($images))
        );
        $this->assertSame(
            3,
            $num = $model->getHits(),
            sprintf('Expected model to have 3 hits, got %d', $num)
        );

        $query->imageIdentifiers([$id1, $id2, $id3, str_repeat('f', 32)]);
        $this->assertCount(
            3,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 3 images, got %d', count($images))
        );
        $this->assertSame(
            3,
            $num = $model->getHits(),
            sprintf('Expected model to have 3 hits, got %d', $num)
        );
    }

    /**
     * @covers ::getImages
     */
    public function testCanFilterOnChecksums() {
        $user = 'user';
        $id1 = 'id1';
        $id2 = 'id2';
        $id3 = 'id3';
        $image1 = $this->getImage()->setChecksum('checksum1');
        $image2 = $this->getImage()->setChecksum('checksum2');
        $image3 = $this->getImage()->setChecksum('checksum3');

        // This is the same for all image objects above
        $originalChecksum = $image1->getOriginalChecksum();

        $this->assertTrue($this->adapter->insertImage($user, $id1, $image1), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user, $id2, $image2), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user, $id3, $image3), 'Could not insert image');

        $query = new Query();
        $model = new Images();

        $query->originalChecksums(['foobar']);
        $this->assertCount(
            0,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 0 images, got %d', count($images))
        );
        $this->assertSame(
            0,
            $num = $model->getHits(),
            sprintf('Expected model to have 0 hits, got %d', $num)
        );

        $query->originalChecksums([$originalChecksum]);
        $this->assertCount(
            3,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 3 images, got %d', count($images))
        );
        $this->assertSame(
            3,
            $num = $model->getHits(),
            sprintf('Expected model to have 3 hits, got %d', $num)
        );

        $query->checksums(['foobar']);
        $this->assertCount(
            0,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 0 images, got %d', count($images))
        );
        $this->assertSame(
            0,
            $num = $model->getHits(),
            sprintf('Expected model to have 0 hits, got %d', $num)
        );

        $query->checksums(['checksum1']);
        $this->assertCount(
            1,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 1 image, got %d', count($images))
        );
        $this->assertSame(
            1,
            $num = $model->getHits(),
            sprintf('Expected model to have 1 hit, got %d', $num)
        );

        $query->checksums(['checksum1', 'checksum2']);
        $this->assertCount(
            2,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 2 images, got %d', count($images))
        );
        $this->assertSame(
            2,
            $num = $model->getHits(),
            sprintf('Expected model to have 2 hits, got %d', $num)
        );

        $query->checksums(['checksum1', 'checksum2', 'checksum3']);
        $this->assertCount(
            3,
            $images = $this->adapter->getImages([$user], $query, $model),
            sprintf('Expected 2 images, got %d', count($images))
        );
        $this->assertSame(
            3,
            $num = $model->getHits(),
            sprintf('Expected model to have 3 hits, got %d', $num)
        );
    }

    /**
     * @covers ::insertImage
     * @covers ::getImages
     */
    public function testCanFilterImagesByUser() {
        $user1 = 'user1';
        $user2 = 'user2';
        $user3 = 'user3';

        $id1 = 'id1';
        $id2 = 'id2';
        $id3 = 'id3';

        $image1 = $this->getImage()->setChecksum('checksum1');
        $image2 = $this->getImage()->setChecksum('checksum2');
        $image3 = $this->getImage()->setChecksum('checksum3');

        // This is the same for all image objects above
        $originalChecksum = $image1->getOriginalChecksum();

        $this->assertTrue($this->adapter->insertImage($user1, $id1, $image1), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user2, $id1, $image1), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user2, $id2, $image2), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user3, $id1, $image1), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user3, $id2, $image2), 'Could not insert image');
        $this->assertTrue($this->adapter->insertImage($user3, $id3, $image3), 'Could not insert image');

        $model = new Images();

        $this->assertCount(
            1,
            $images = $this->adapter->getImages([$user1], new Query(), $model),
            sprintf('Expected 1 image, got %d', count($images))
        );
        $this->assertSame(
            1,
            $num = $model->getHits(),
            sprintf('Expected model to have 1 hit, got %d', $num)
        );

        $this->assertCount(
            2,
            $images = $this->adapter->getImages([$user2], new Query(), $model),
            sprintf('Expected 2 images, got %d', count($images))
        );
        $this->assertSame(
            2,
            $num = $model->getHits(),
            sprintf('Expected model to have 2 hits, got %d', $num)
        );

        $this->assertCount(
            3,
            $images = $this->adapter->getImages([$user3], new Query(), $model),
            sprintf('Expected 3 images, got %d', count($images))
        );
        $this->assertSame(
            3,
            $num = $model->getHits(),
            sprintf('Expected model to have 3 hits, got %d', $num)
        );

        $this->assertCount(
            3,
            $images = $this->adapter->getImages([$user1, $user2], new Query(), $model),
            sprintf('Expected 3 images, got %d', count($images))
        );
        $this->assertSame(
            3,
            $num = $model->getHits(),
            sprintf('Expected model to have 3 hits, got %d', $num)
        );

        $this->assertCount(
            4,
            $images = $this->adapter->getImages([$user1, $user3], new Query(), $model),
            sprintf('Expected 4 images, got %d', count($images))
        );
        $this->assertSame(
            4,
            $num = $model->getHits(),
            sprintf('Expected model to have 4 hits, got %d', $num)
        );

        $this->assertCount(
            5,
            $images = $this->adapter->getImages([$user2, $user3], new Query(), $model),
            sprintf('Expected 5 images, got %d', count($images))
        );
        $this->assertSame(
            5,
            $num = $model->getHits(),
            sprintf('Expected model to have 5 hits, got %d', $num)
        );

        // @see https://github.com/imbo/imbo/issues/552
        $this->assertCount(
            6,
            $images = $this->adapter->getImages([], new Query(), $model),
            sprintf('Expected 6 images, got %d', count($images))
        );
        $this->assertSame(
            6,
            $num = $model->getHits(),
            sprintf('Expected model to have 6 hits, got %d', $num)
        );
    }

    /**
     * @covers ::getNumBytes
     */
    public function testCanGetNumberOfBytes() {
        $this->assertSame(
            0,
            $num = $this->adapter->getNumBytes('user'),
            sprintf('Expected 0 bytes, got %d', $num)
        );

        $this->adapter->insertImage('user', 'id', $this->getImage(), 'Could not insert image');
        $this->assertSame(
            3456,
            $num = $this->adapter->getNumBytes('user'),
            sprintf('Expected 3456 bytes, got %d', $num)
        );

        $this->adapter->insertImage('user2', 'id', $this->getImage(), 'Could not insert image');
        $this->assertSame(
            3456,
            $num = $this->adapter->getNumBytes('user2'),
            sprintf('Expected 3456 bytes, got %d', $num)
        );

        $this->assertSame(
            6912,
            $num = $this->adapter->getNumBytes(),
            sprintf('Expected 6912 bytes, got %d', $num)
        );
    }

    /**
     * @covers ::getNumUsers
     */
    public function testCanGetNumberOfUsers() {
        $this->adapter->insertImage('user', 'id', $this->getImage(), 'Could not insert image');
        $this->assertSame(
            1,
            $num = $this->adapter->getNumUsers(),
            sprintf('Expected 1 user, got %d', $num)
        );

        $this->adapter->insertImage('user2', 'id', $this->getImage(), 'Could not insert image');
        $this->assertSame(
            2,
            $num = $this->adapter->getNumUsers(),
            sprintf('Expected 2 users, got %d', $num)
        );
    }

    /**
     * Get parameters to test sorting
     *
     * @return array
     */
    public function getSortData() {
        return [
            'no sorting' => [
                'sort' => null,
                'field' => 'imageIdentifier',
                'values' => [
                    'a501051db16e3cbf88ea50bfb0138a47',
                    '1d5b88aec8a3e1c4c57071307b2dae3a',
                    'b914b28f4d5faa516e2049b9a6a2577c',
                    'fc7d2d06993047a0b5056e8fac4462a2',
                    '929db9c5fc3099f7576f5655207eba47',
                    'f3210f1bb34bfbfa432cc3560be40761',
                ],
            ],
            'default sort on size' => [
                'sort' => ['size'],
                'field' => 'size',
                'values' => [
                    41423,
                    64828,
                    74337,
                    84988,
                    92795,
                    95576,
                ],
            ],
            'desc sort on size' => [
                'sort' => ['size:desc'],
                'field' => 'size',
                'values' => [
                    95576,
                    92795,
                    84988,
                    74337,
                    64828,
                    41423,
                ],
            ],
            'sort on multiple fields' => [
                'sort' => ['width:asc', 'size:desc'],
                'field' => 'size',
                'values' => [
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
     * @covers ::getImages
     * @param array $sort
     * @param string $field
     * @param mixed[] $values
     */
    public function testCanSortImages(array $sort = null, $field, array $values) {
        $this->insertImages();

        $query = new Query();

        if ($sort !== null) {
            $query->sort($sort);
        }

        $images = $this->adapter->getImages(['user'], $query, $this->createMock('Imbo\Model\Images'));

        foreach ($images as $i => $image) {
            $this->assertSame(
                $values[$i],
                $image[$field],
                sprintf(
                    'Incorrectly sorted images. Expected "%s" on index %d, got "%s"',
                    $values[$i],
                    $i,
                    $image[$field]
                )
            );
        }
    }

    /**
     * @covers ::getStatus
     */
    public function testCanGetStatus() {
        $this->assertTrue($this->adapter->getStatus(), 'Expected status to be true');
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForAllUsers() {
        // Default image
        $image = [
            'user' => 'user',
            'imageIdentifier' => uniqid(),
            'size' => 12505,
            'extension' => 'png',
            'mime' => 'image/png',
            'added' => 1499234238,
            'updated' => 1499234238,
            'width' => 1024,
            'height' => 256,
            'checksum' => 'b60df41830245ee8f278e3ddfe5238a3',
            'originalChecksum' => 'b60df41830245ee8f278e3ddfe5238a3',
        ];

        return [
            'no images' => [
                'images' => [],
                'expectedUsers' => [],
            ],
            'images with different users' => [
                'images' => [
                    ['user' => 'user1', 'imageIdentifier' => uniqid('imbo-', true)] + $image,
                    ['user' => 'user3', 'imageIdentifier' => uniqid('imbo-', true)] + $image,
                    ['user' => 'user1', 'imageIdentifier' => uniqid('imbo-', true)] + $image,
                    ['user' => 'user2', 'imageIdentifier' => uniqid('imbo-', true)] + $image,
                    ['user' => 'user2', 'imageIdentifier' => uniqid('imbo-', true)] + $image,
                    ['user' => 'user2', 'imageIdentifier' => uniqid('imbo-', true)] + $image,
                ],
                'expectedUsers' => ['user1', 'user2', 'user3'],
            ],
        ];
    }

    /**
     * @dataProvider getDataForAllUsers
     * @covers ::getAllUsers
     * @param array $images Images to insert
     * @param string[] $expectedUsers Users expected to be returned
     */
    public function testCanGetAllUsers(array $images, array $expectedUsers) {
        array_map([$this, 'insertImage'], $images);
        $this->assertSame($expectedUsers, $this->adapter->getAllUsers(), 'Incorrect list of users');
    }
}
