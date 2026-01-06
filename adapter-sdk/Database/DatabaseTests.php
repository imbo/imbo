<?php declare(strict_types=1);
namespace ImboSDK\Database;

use DateTime;
use Imbo\Database\DatabaseInterface;
use Imbo\Exception\DatabaseException;
use Imbo\Exception\DuplicateImageIdentifierException;
use Imbo\Model\Image;
use Imbo\Model\Images;
use Imbo\Resource\Images\Query;
use ImboSDK\Constraint\MultidimensionalArrayIsEqual;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Base test case for database adapters
 */
abstract class DatabaseTests extends TestCase
{
    protected DatabaseInterface $adapter;
    protected static string $fixturesDir  = __DIR__ . '/../Fixtures';
    protected int $allowedTimestampDelta = 1;

    /**
     * Get the adapter to be tested
     */
    abstract protected function getAdapter(): DatabaseInterface;

    protected function setUp(): void
    {
        $this->adapter = $this->getAdapter();
    }

    /**
     * Get an image model based on a file in the fixtures directory
     *
     * @param string $image One of the images in the fixtures directory
     * @throws RuntimeException
     * @return Image
     */
    private static function getImageModel(string $image = 'test-image.png', ?int $added = null, ?int $updated = null): Image
    {
        $file = self::$fixturesDir . '/' . $image;

        if (!file_exists($file)) {
            throw new RuntimeException(sprintf('Image files does not exist: %s', $file));
        }

        if (false === ($size = getimagesize($file))) {
            throw new RuntimeException(sprintf('Unable to get image size for: %s', $file));
        }

        [$width, $height, $type] = $size;

        $image = (new Image())
            ->setBlob((string) file_get_contents($file))
            ->setWidth((int) $width)
            ->setHeight((int) $height)
            ->setFilesize((int) filesize($file))
            ->setMimeType(image_type_to_mime_type((int) $type))
            ->setExtension(substr($file, (int) strrpos($file, '.') + 1))
            ->setOriginalChecksum(md5($file));

        if (null !== $added) {
            $image->setAddedDate(new DateTime('@' . $added));
        }

        if (null !== $updated) {
            $image->setUpdatedDate(new DateTime('@' . $updated));
        }

        return $image;
    }

    public function testCanInsertAndGetImage(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, self::getImageModel('image.jpg')),
            'Unable to insert image',
        );

        $image = new Image();
        $this->assertTrue(
            $this->adapter->load($user, $imageIdentifier, $image),
            'Unable to load image',
        );

        $this->assertSame(665, $image->getWidth(), 'Image width is incorrect');
        $this->assertSame(463, $image->getHeight(), 'Image height is incorrect');
        $this->assertSame('image/jpeg', $image->getMimeType(), 'Image mime type is incorrect');
        $this->assertSame(64828, $image->getFilesize(), 'Image filesize is incorrect');
        $this->assertSame('jpg', $image->getExtension(), 'Image extension is incorrect');

        $properties = $this->adapter->getImageProperties($user, $imageIdentifier);

        foreach (['size', 'extension', 'mime', 'added', 'updated', 'width', 'height'] as $requiredKey) {
            $this->assertArrayHasKey(
                $requiredKey,
                $properties,
                sprintf(
                    'Image properties is missing a required key: "%s"',
                    $requiredKey,
                ),
            );
        }

        $this->assertSame(64828, $properties['size'], 'Incorrect size in image properties');
        $this->assertSame('jpg', $properties['extension'], 'Incorrect extension in image properties');
        $this->assertSame('image/jpeg', $properties['mime'], 'Incorrect mime in image properties');
        $this->assertEqualsWithDelta(time(), $properties['added'], 2, 'Incorrect added timestamp in image properties');
        $this->assertEqualsWithDelta(time(), $properties['updated'], 2, 'Incorrect updated timestamp in image properties');
        $this->assertSame(665, $properties['width'], 'Incorrect size in image properties');
        $this->assertSame(463, $properties['height'], 'Incorrect size in image properties');
    }

    public function testGetImagePropertiesOfImageThatDoesNotExist(): void
    {
        $this->expectExceptionObject(new DatabaseException(
            'Image not found',
            404,
        ));
        $this->adapter->getImageProperties('user', 'image-id');
    }

    public function testStoreSameImageTwiceWithoutUpdateIfDuplicate(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';
        $image           = self::getImageModel();

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $image, false),
            'Unable to insert image',
        );

        $this->expectExceptionObject(new DuplicateImageIdentifierException(
            'Duplicate image identifier when attempting to insert image into DB.',
            503,
        ));

        $this->adapter->insertImage($user, $imageIdentifier, $image, false);
    }

    public function testCanDeleteImages(): void
    {
        $this->assertFalse(
            $this->adapter->imageExists('user', 'id'),
            'Did not expect image to exist',
        );
        $this->assertTrue(
            $this->adapter->insertImage('user', 'id', self::getImageModel()),
            'Unable to insert image',
        );
        $this->assertTrue(
            $this->adapter->imageExists('user', 'id'),
            'Expected image to exist',
        );
        $this->assertTrue(
            $this->adapter->deleteImage('user', 'id'),
            'Unable to delete image',
        );
        $this->assertFalse(
            $this->adapter->imageExists('user', 'id'),
            'Did not expect image to exist',
        );

        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->load('user', 'id', $this->createStub(Image::class));
    }

    public function testDeleteImageThatDoesNotExist(): void
    {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->deleteImage('user', 'id');
    }

    public function testLoadImageThatDoesNotExist(): void
    {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->load('user', 'id', $this->createStub(Image::class));
    }

    /**
     * @param array<string> $users
     */
    #[DataProvider('getUsers')]
    public function testGetLastModifiedOfImageThatDoesNotExist(array $users): void
    {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->getLastModified($users, 'id');
    }

    /**
     * Expected timestamp are returned as strings instead of actual DateTime instances, because
     * "now" would otherwise have a too big delta compared to when the test will run
     *
     * @return array<string, array{
     *   images: Image[],
     *   users: string[],
     *   imageIdentifier: ?string,
     *   expectedDateTime: string
     * }>
     */
    public static function getDataForLastModificationTest(): array
    {
        $image = self::getImageModel('test-image.png', 1499234238, 1499234238)
            ->setUser('user')
            ->setImageIdentifier(uniqid());
        $image2 = clone $image;
        $image3 = clone $image;
        $image4 = clone $image;
        $image5 = clone $image;
        $image6 = clone $image;

        return [
            'empty database / users / no image identifier' => [
                'images'               => [],
                'users'                => ['someuser', 'someotheruser'],
                'imageIdentifier'      => null,
                'expectedDateTime'     => 'now',
            ],

            'images / users with no hit / no image identifier' => [
                'images'               => [$image],
                'users'                => ['someuser'],
                'imageIdentifier'      => null,
                'expectedDateTime'     => 'now',
            ],

            'images / users with one hit / no image identifier' => [
                'images'               => [$image],
                'users'                => ['user'],
                'imageIdentifier'      => null,
                'expectedDateTime'     => '@1499234238',
            ],

            'images / multiple users with hits / no image identifier' => [
                'images'               => [
                    $image2
                        ->setUpdatedDate(new DateTime('@123'))
                        ->setUser('user1')
                        ->setImageIdentifier(uniqid()),
                    $image3
                        ->setUpdatedDate(new Datetime('@124'))
                        ->setUser('user2')
                        ->setImageIdentifier(uniqid()),
                    $image4
                        ->setUpdatedDate(new DateTime('@129'))
                        ->setUser('user2')
                        ->setImageIdentifier(uniqid()),
                    $image5
                        ->setUpdatedDate(new DateTime('@126'))
                        ->setUser('user1')
                        ->setImageIdentifier(uniqid()),
                    $image6
                        ->setUpdatedDate(new DateTime('@124'))
                        ->setUser('user2')
                        ->setImageIdentifier(uniqid()),
                ],
                'users'                => ['user', 'user1', 'user2', 'user3'],
                'imageIdentifier'      => null,
                'expectedDateTime'     => '@129',
            ],
        ];
    }

    /**
     * @param array<Image> $images
     * @param array<string> $users
     * @param string $imageIdentifier
     * @param string $expectedDateTime
     */
    #[DataProvider('getDataForLastModificationTest')]
    public function testCanGetLastModifiedDate(array $images, array $users, ?string $imageIdentifier, string $expectedDateTime): void
    {
        foreach ($images as $image) {
            $this->adapter->insertImage(
                (string) $image->getUser(),
                (string) $image->getImageIdentifier(),
                $image,
            );
        }

        $lastModified = $this->adapter->getLastModified($users, $imageIdentifier);

        $this->assertEqualsWithDelta(
            (new DateTime($expectedDateTime))->getTimestamp(),
            $lastModified->getTimestamp(),
            $this->allowedTimestampDelta,
            'Incorrect DateTime value returned from getLastModified',
        );
    }

    public function testCanSetLastModifiedDateToNow(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';
        $added           = time() - 10;
        $original        = self::getImageModel('test-image.png', $added, $added);

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, $original),
            'Unable to insert image',
        );

        $now = $this->adapter->setLastModifiedNow($user, $imageIdentifier);
        $this->assertEqualsWithDelta(
            time(),
            $now->getTimestamp(),
            $this->allowedTimestampDelta,
            'Returned timestamp should be around now',
        );

        $image = new Image();

        $this->assertTrue(
            $this->adapter->load($user, $imageIdentifier, $image),
            'Unable to load image',
        );

        /** @var DateTime */
        $datetime = $image->getAddedDate();

        $this->assertEquals(
            $added,
            $datetime->getTimestamp(),
            'Added timestamp should not be modified',
        );

        /** @var DateTime */
        $updated = $image->getUpdatedDate();

        $this->assertEquals(
            $now->getTimestamp(),
            $updated->getTimestamp(),
            'Updated timestamp should have updated',
        );

        $lastModified = $this->adapter->getLastModified([$user], $imageIdentifier);
        $this->assertEquals(
            $now->getTimestamp(),
            $lastModified->getTimestamp(),
            'Last timestamp should have updated',
        );
    }

    public function testCanSetLastModifiedDateToTimestamp(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, self::getImageModel()),
            'Unable to insert image',
        );

        $desired  = new DateTime('@' . (time() + 10));
        $returned = $this->adapter->setLastModifiedTime($user, $imageIdentifier, $desired);

        $this->assertEquals(
            $desired->getTimestamp(),
            $returned->getTimestamp(),
            'Returned timestamp should be the same as the one set',
        );

        $image = new Image();

        $this->assertTrue(
            $this->adapter->load($user, $imageIdentifier, $image),
            'Unable to load image',
        );

        /** @var DateTime */
        $updated = $image->getUpdatedDate();

        $this->assertEquals(
            $desired->getTimestamp(),
            $updated->getTimestamp(),
            'Updated timestamp should have updated',
        );

        $lastModified = $this->adapter->getLastModified([$user], $imageIdentifier);
        $this->assertEquals(
            $desired->getTimestamp(),
            $lastModified->getTimestamp(),
            'Last timestamp should have updated',
        );
    }

    public function testCannotSetLastModifiedDateForMissingImage(): void
    {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->setLastModifiedNow('user', 'id');
    }

    public function testGetNumImages(): void
    {
        $user  = 'user';
        $image = self::getImageModel();
        $num   = $this->adapter->getNumImages($user);

        $this->assertSame(
            0,
            $num,
            sprintf('Expected 0 images for user %s, got %d', $user, $num),
        );

        $this->assertTrue(
            $this->adapter->insertImage('user2', 'id0', $image),
            'Unable to insert image',
        );

        $num = $this->adapter->getNumImages();

        $this->assertSame(
            1,
            $num,
            sprintf('Expected 1 image, got %d', $num),
        );

        $this->assertTrue(
            $this->adapter->insertImage($user, 'id1', $image),
            'Unable to insert image',
        );

        $num = $this->adapter->getNumImages($user);

        $this->assertSame(
            1,
            $num,
            sprintf('Expected 1 image for user %s, got %d', $user, $num),
        );

        // Insert with same ID
        $this->assertTrue(
            $this->adapter->insertImage($user, 'id1', $image),
            'Unable to insert image',
        );

        $num = $this->adapter->getNumImages($user);

        $this->assertSame(
            1,
            $num,
            sprintf('Expected 1 image for user %s, got %d', $user, $num),
        );

        // Insert with a new ID
        $this->assertTrue(
            $this->adapter->insertImage($user, 'id2', $image),
            'Unable to insert image',
        );

        $num = $this->adapter->getNumImages($user);

        $this->assertSame(
            2,
            $num,
            sprintf('Expected 2 images for user %s, got %d', $user, $num),
        );

        $num = $this->adapter->getNumImages();

        $this->assertSame(
            3,
            $num,
            sprintf('Expected 3 images, got %d', $num),
        );
    }

    public function testGetMetadataWhenImageDoesNotExist(): void
    {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->getMetadata('user', 'id');
    }

    public function testGetMetadataWhenImageHasNone(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, self::getImageModel()),
            'Unable to insert image',
        );

        $this->assertSame(
            [],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Expected metadata to be empty',
        );
    }

    public function testUpdateAndGetMetadata(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, self::getImageModel()),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'bar']),
            'Unable to update metadata',
        );

        $this->assertSame(
            ['foo' => 'bar'],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect',
        );

        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'foo', 'bar' => 'foo']),
            'Unable to update metadata',
        );

        $this->assertMultidimensionalArraysAreEqual(
            ['foo' => 'foo', 'bar' => 'foo'],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect',
        );
    }

    public function testMetadataWithNestedArraysIsRepresetedCorrectly(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';
        $metadata        = [
            'string'  => 'bar',
            'integer' => 1,
            'float'   => 1.1,
            'boolean' => true,
            'list'    => [1, 2, 3],
            'assoc'   => [
                'string'  => 'bar',
                'integer' => 1,
                'float'   => 1.1,
                'boolean' => false,
                'list'    => [1, 2, 3],
                'assoc'   => [
                    'list' => [
                        1,
                        2, [
                            'list' => [1, 2, 3],
                        ],
                        [1, 2, 3],
                    ],
                ],
            ],
        ];

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, self::getImageModel()),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, $metadata),
            'Unable to update metadata',
        );

        $this->assertMultidimensionalArraysAreEqual(
            $metadata,
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect',
        );
    }

    public function testMetadataWithNestedArraysIsRepresetedCorrectlyWhenFetchingMultipleImages(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';
        $metadata        = [
            'string'  => 'bar',
            'integer' => 1,
            'float'   => 1.1,
            'boolean' => true,
            'list'    => [1, 2, 3],
            'assoc'   => [
                'string'  => 'bar',
                'integer' => 1,
                'float'   => 1.1,
                'boolean' => false,
                'list'    => [1, 2, 3],
                'assoc'   => [
                    'list' => [
                        1,
                        2, [
                            'list' => [1, 2, 3],
                        ],
                        [1, 2, 3],
                    ],
                ],
            ],
        ];

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, self::getImageModel()),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, $metadata),
            'Unable to update metadata',
        );

        $query = new Query();
        $query->setReturnMetadata(true);

        $images = $this->adapter->getImages(['user'], $query, new Images());

        $this->assertCount(
            1,
            $images,
            'Expected array to have exactly one image',
        );

        /** @var array<string,mixed> */
        $imageMetadata = $images[0]['metadata'];

        $this->assertMultidimensionalArraysAreEqual(
            $metadata,
            $imageMetadata,
            'Metadata is incorrect',
        );
    }

    public function testUpdateDeleteAndGetMetadata(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertImage($user, $imageIdentifier, self::getImageModel()),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->updateMetadata($user, $imageIdentifier, ['foo' => 'bar']),
            'Unable to update metadata',
        );

        $this->assertSame(
            ['foo' => 'bar'],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect',
        );

        $this->assertTrue(
            $this->adapter->deleteMetadata($user, $imageIdentifier),
            'Unable to delete metadata',
        );

        $this->assertSame(
            [],
            $this->adapter->getMetadata($user, $imageIdentifier),
            'Metadata is incorrect',
        );
    }

    public function testDeleteMetataFromImageThatDoesNotExist(): void
    {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->deleteMetadata('user', 'id');
    }

    public function testGetImagesWithStartAndEndTimestamps(): void
    {
        [$start, $end] = $this->insertImages();
        $model         = new Images();
        $user          = 'user';

        // Fetch to the timestamp of when the last image was added
        $query = new Query();
        $query->setTo($end);

        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            6,
            $images,
            sprintf('Expected 6 images, got %d', count($images)),
        );

        $hits = $model->getHits();

        $this->assertSame(
            6,
            $hits,
            sprintf('Incorrect hits value in model. Expected 6, got %d', $hits),
        );

        // Fetch until the second the first image was added
        $query = new Query();
        $query->setTo($start);

        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            1,
            $images,
            sprintf('Expected 1 image, got %d', count($images)),
        );

        $hits = $model->getHits();

        $this->assertSame(
            1,
            $hits,
            sprintf('Incorrect hits value in model. Expected 6, got %d', $hits),
        );

        // Fetch from the second the first image was added
        $query = new Query();
        $query->setFrom($start);

        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            6,
            $images,
            sprintf('Expected 6 images, got %d', count($images)),
        );

        $hits = $model->getHits();

        $this->assertSame(
            6,
            $hits,
            sprintf('Incorrect hits value in model. Expected 6, got %d', $hits),
        );

        // Fetch from the second the last image was added
        $query = new Query();
        $query->setFrom($end);

        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            1,
            $images,
            sprintf('Expected 1 image, got %d', count($images)),
        );

        $hits = $model->getHits();

        $this->assertSame(
            1,
            $hits,
            sprintf('Incorrect hits value in model. Expected 6, got %d', $hits),
        );
    }

    /**
     * @see https://github.com/imbo/imbo/pull/491
     */
    public function testGetImagesAndReturnMetadata(): void
    {
        $this->insertImages(true);

        $query = new Query();
        $query->setReturnMetadata(true);

        $images = $this->adapter->getImages(['user', 'user2'], $query, $this->createStub(Images::class));

        $this->assertCount(
            6,
            $images,
            sprintf('Expected 6 images, got %d', count($images)),
        );

        foreach ($images as $image) {
            $this->assertArrayHasKey('metadata', $image, 'Image is missing metadata');
        }

        $this->assertSame('user', $images[0]['user'], 'Incorrect user');
        $this->assertSame(['key5' => 'value5'], $images[0]['metadata'], 'Incorrect metadata');

        $this->assertSame('user2', $images[1]['user'], 'Incorrect user');
        $this->assertSame(['key4' => 'value4'], $images[1]['metadata'], 'Incorrect metadata');

        $this->assertSame('user', $images[2]['user'], 'Incorrect user');
        $this->assertSame(['key3' => 'value3'], $images[2]['metadata'], 'Incorrect metadata');

        $this->assertSame('user2', $images[3]['user'], 'Incorrect user');
        $this->assertSame(['key2' => 'value2'], $images[3]['metadata'], 'Incorrect metadata');

        $this->assertSame('user', $images[4]['user'], 'Incorrect user');
        $this->assertSame(['key1' => 'value1'], $images[4]['metadata'], 'Incorrect metadata');

        $this->assertSame('user2', $images[5]['user'], 'Incorrect user');
        $this->assertSame(['key0' => 'value0'], $images[5]['metadata'], 'Incorrect metadata');
    }

    /**
     * @return array<string,array{page:?int,limit:?int,imageIdentifiers:array<string>}>
     */
    public static function getPageAndLimit(): array
    {
        return [
            'no page or limit' => [
                'page'             => 0,
                'limit'            => 0,
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
                'page'             => 0,
                'limit'            => 2,
                'imageIdentifiers' => [
                    'a501051db16e3cbf88ea50bfb0138a47',
                    '1d5b88aec8a3e1c4c57071307b2dae3a',
                ],
            ],
            'first page, 2 images' => [
                'page'             => 1,
                'limit'            => 2,
                'imageIdentifiers' => [
                    'a501051db16e3cbf88ea50bfb0138a47',
                    '1d5b88aec8a3e1c4c57071307b2dae3a',
                ],
            ],
            'second page, 2 images' => [
                'page'             => 2,
                'limit'            => 2,
                'imageIdentifiers' => [
                    'b914b28f4d5faa516e2049b9a6a2577c',
                    'fc7d2d06993047a0b5056e8fac4462a2',
                ],
            ],
            'second page, 4 images' => [
                'page'             => 2,
                'limit'            => 4,
                'imageIdentifiers' => [
                    '929db9c5fc3099f7576f5655207eba47',
                    'f3210f1bb34bfbfa432cc3560be40761',
                ],
            ],
            'fourth page, 2 images' => [
                'page'             => 4,
                'limit'            => 2,
                'imageIdentifiers' => [],
            ],
        ];
    }

    /**
     * @param array<string> $imageIdentifiers
     */
    #[DataProvider('getPageAndLimit')]
    public function testGetImagesWithPageAndLimit(int $page, int $limit, array $imageIdentifiers): void
    {
        $this->insertImages();

        $query = new Query();

        if (0 !== $page) {
            $query->setPage($page);
        }

        if (0 !== $limit) {
            $query->setLimit($limit);
        }

        $model = $this->createMock(Images::class);
        $model
            ->expects($this->once())
            ->method('setHits')
            ->with(6);

        $images = $this->adapter->getImages(['user'], $query, $model);
        $num    = count($imageIdentifiers);

        $this->assertCount(
            $num,
            $images,
            sprintf('Expected %d images, got %d', $num, count($images)),
        );

        foreach ($images as $i => $image) {
            $this->assertSame(
                $imageIdentifiers[$i],
                $image['imageIdentifier'],
                'Incorrect image identifier',
            );
        }
    }

    public function testPageIsNotAllowedWithoutLimitWhenGettingImages(): void
    {
        $query = new Query();
        $query->setLimit(0);
        $query->setPage(3);

        $this->expectExceptionObject(new DatabaseException(
            'page is not allowed without limit',
            400,
        ));
        $this->adapter->getImages(['user'], $query, $this->createStub(Images::class));
    }

    public function testGetImageMimeType(): void
    {
        $user   = 'user';
        $images = [
            self::getImageModel('image.png'),
            self::getImageModel('image.jpg'),
        ];

        foreach ($images as $image) {
            $this->assertTrue(
                $this->adapter->insertImage($user, md5((string) $image->getBlob()), $image),
                'Unable to add image',
            );
        }

        $mimeType = $this->adapter->getImageMimeType($user, md5((string) $images[0]->getBlob()));

        $this->assertSame(
            'image/png',
            $mimeType,
            sprintf('Incorrect mime type. Expected image/png, got %s', $mimeType),
        );

        $mimeType = $this->adapter->getImageMimeType($user, md5((string) $images[1]->getBlob()));

        $this->assertSame(
            'image/jpeg',
            $mimeType,
            sprintf('Incorrect mime type. Expected image/jpeg, got %s', $mimeType),
        );
    }

    public function testGetMimeTypeWhenImageDoesNotExist(): void
    {
        $this->expectExceptionObject(new DatabaseException('Image not found', 404));
        $this->adapter->getImageMimeType('user', 'id');
    }

    /**
     * @return array<string, array{
     *   shortUrlId: string,
     *   query?: array<string, string|array<string>>,
     *   extension?: string
     * }>
     */
    public static function getShortUrlVariations(): array
    {
        return [
            'without query and extension' => [
                'shortUrlId' => 'aaaaaaa',
            ],
            'with query and extension' => [
                'shortUrlId' => 'bbbbbbb',
                'query' => [
                    't' => [
                        'thumbnail:width=40',
                    ],
                    'accessToken' => 'token',
                ],
                'extension' => 'png',
            ],
            'with query' => [
                'shortUrlId' => 'ccccccc',
                'query' => [
                    't' => [
                        'thumbnail:width=40',
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
     * @param array<string,string|array<string>> $query
     */
    #[DataProvider('getShortUrlVariations')]
    public function testCanInsertAndGetParametersForAShortUrl(string $shortUrlId, array $query = [], ?string $extension = null): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertShortUrl($shortUrlId, $user, $imageIdentifier, $extension, $query),
            'Unable to insert short URL',
        );

        /** @var array{user: string, imageIdentifier: string, extension: string, query: array<string, string>} */
        $params = $this->adapter->getShortUrlParams($shortUrlId);

        $this->assertSame(
            $user,
            $params['user'],
            sprintf('Incorrect user. Expected "%s", got "%s"', $user, $params['user']),
        );

        $this->assertSame(
            $imageIdentifier,
            $params['imageIdentifier'],
            sprintf('Incorrect image identifier. Expected "%s", got "%s"', $imageIdentifier, $params['imageIdentifier']),
        );

        $this->assertSame(
            $extension,
            $params['extension'],
            sprintf('Incorrect extension. Expected "%s", got "%s"', (string) $extension, $params['extension']),
        );

        $this->assertSame($query, $params['query'], 'Incorrect query');

        $id = $this->adapter->getShortUrlId($user, $imageIdentifier, $extension, $query);

        $this->assertSame(
            $shortUrlId,
            $id,
            sprintf(
                'Incorrect short URL ID. Expected "%s", got "%s"',
                $shortUrlId,
                (string) $id,
            ),
        );
    }

    public function testCanGetShortUrlIdThatDoesNotExist(): void
    {
        $this->assertNull($this->adapter->getShortUrlId('user', 'image'));
    }

    public function testCanDeleteShortUrls(): void
    {
        $shortUrlId      = 'aaaaaaa';
        $user            = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertShortUrl($shortUrlId, $user, $imageIdentifier),
            'Unable to insert short URL',
        );

        $this->assertTrue(
            $this->adapter->deleteShortUrls($user, $imageIdentifier),
            'Unable to delete short URLs',
        );

        $this->assertNull(
            $this->adapter->getShortUrlParams($shortUrlId),
            'Did not expect to get short URL params',
        );
    }

    public function testCanDeleteASingleShortUrl(): void
    {
        $user            = 'user';
        $imageIdentifier = 'id';

        $this->assertTrue(
            $this->adapter->insertShortUrl('aaaaaaa', $user, $imageIdentifier),
            'Unable to insert short URL',
        );

        $this->assertTrue(
            $this->adapter->insertShortUrl('bbbbbbb', $user, $imageIdentifier),
            'Unable to insert short URL',
        );

        $this->assertTrue(
            $this->adapter->insertShortUrl('ccccccc', $user, $imageIdentifier),
            'Unable to insert short URL',
        );

        $this->assertTrue(
            $this->adapter->deleteShortUrls($user, $imageIdentifier, 'aaaaaaa'),
            'Unable to delete short URLs',
        );

        $this->assertNull(
            $this->adapter->getShortUrlParams('aaaaaaa'),
            'Did not expect to get short URL params',
        );

        $this->assertNotNull(
            $this->adapter->getShortUrlParams('bbbbbbb'),
            'Expected short URL params',
        );

        $this->assertNotNull(
            $this->adapter->getShortUrlParams('ccccccc'),
            'Expected short URL params',
        );
    }

    public function testCanFilterOnImageIdentifiers(): void
    {
        $user  = 'user';
        $id1   = 'id1';
        $id2   = 'id2';
        $id3   = 'id3';
        $image = self::getImageModel();

        $this->assertTrue(
            $this->adapter->insertImage($user, $id1, $image),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user, $id2, $image),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user, $id3, $image),
            'Unable to insert image',
        );

        $query = new Query();
        $model = new Images();

        $query->setImageIdentifiers([$id1]);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            1,
            $images,
            sprintf('Expected 1 image, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            1,
            $num,
            sprintf('Expected model to have 1 hit, got %d', $num),
        );

        $query->setImageIdentifiers([$id1, $id2]);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            2,
            $images,
            sprintf('Expected 2 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            2,
            $num,
            sprintf('Expected model to have 2 hits, got %d', $num),
        );

        $query->setImageIdentifiers([$id1, $id2, $id3]);

        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            3,
            $images,
            sprintf('Expected 3 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            3,
            $num,
            sprintf('Expected model to have 3 hits, got %d', $num),
        );

        $query->setImageIdentifiers([$id1, $id2, $id3, str_repeat('f', 32)]);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            3,
            $images,
            sprintf('Expected 3 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            3,
            $num,
            sprintf('Expected model to have 3 hits, got %d', $num),
        );
    }

    public function testCanFilterOnChecksums(): void
    {
        $user   = 'user';
        $id1    = 'id1';
        $id2    = 'id2';
        $id3    = 'id3';
        $image1 = self::getImageModel()->setChecksum('checksum1');
        $image2 = self::getImageModel()->setChecksum('checksum2');
        $image3 = self::getImageModel()->setChecksum('checksum3');

        // This is the same for all image objects above
        $originalChecksum = (string) $image1->getOriginalChecksum();

        $this->assertTrue(
            $this->adapter->insertImage($user, $id1, $image1),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user, $id2, $image2),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user, $id3, $image3),
            'Unable to insert image',
        );

        $query = new Query();
        $model = new Images();

        $query->setOriginalChecksums(['foobar']);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            0,
            $images,
            sprintf('Expected 0 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            0,
            $num,
            sprintf('Expected model to have 0 hits, got %d', $num),
        );

        $query->setOriginalChecksums([$originalChecksum]);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            3,
            $images,
            sprintf('Expected 3 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            3,
            $num,
            sprintf('Expected model to have 3 hits, got %d', $num),
        );

        $query->setChecksums(['foobar']);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            0,
            $images,
            sprintf('Expected 0 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            0,
            $num,
            sprintf('Expected model to have 0 hits, got %d', $num),
        );

        $query->setChecksums(['checksum1']);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            1,
            $images,
            sprintf('Expected 1 image, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            1,
            $num,
            sprintf('Expected model to have 1 hit, got %d', $num),
        );

        $query->setChecksums(['checksum1', 'checksum2']);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            2,
            $images,
            sprintf('Expected 2 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            2,
            $num,
            sprintf('Expected model to have 2 hits, got %d', $num),
        );

        $query->setChecksums(['checksum1', 'checksum2', 'checksum3']);
        $images = $this->adapter->getImages([$user], $query, $model);

        $this->assertCount(
            3,
            $images,
            sprintf('Expected 2 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            3,
            $num,
            sprintf('Expected model to have 3 hits, got %d', $num),
        );
    }

    public function testCanFilterImagesByUser(): void
    {
        $user1  = 'user1';
        $user2  = 'user2';
        $user3  = 'user3';
        $id1    = 'id1';
        $id2    = 'id2';
        $id3    = 'id3';
        $image1 = self::getImageModel()->setChecksum('checksum1');
        $image2 = self::getImageModel()->setChecksum('checksum2');
        $image3 = self::getImageModel()->setChecksum('checksum3');

        $this->assertTrue(
            $this->adapter->insertImage($user1, $id1, $image1),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user2, $id1, $image1),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user2, $id2, $image2),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user3, $id1, $image1),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user3, $id2, $image2),
            'Unable to insert image',
        );

        $this->assertTrue(
            $this->adapter->insertImage($user3, $id3, $image3),
            'Unable to insert image',
        );

        $model = new Images();
        $images = $this->adapter->getImages([$user1], new Query(), $model);

        $this->assertCount(
            1,
            $images,
            sprintf('Expected 1 image, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            1,
            $num,
            sprintf('Expected model to have 1 hit, got %d', $num),
        );

        $images = $this->adapter->getImages([$user2], new Query(), $model);

        $this->assertCount(
            2,
            $images,
            sprintf('Expected 2 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            2,
            $num,
            sprintf('Expected model to have 2 hits, got %d', $num),
        );

        $images = $this->adapter->getImages([$user3], new Query(), $model);

        $this->assertCount(
            3,
            $images,
            sprintf('Expected 3 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            3,
            $num,
            sprintf('Expected model to have 3 hits, got %d', $num),
        );

        $images = $this->adapter->getImages([$user1, $user2], new Query(), $model);

        $this->assertCount(
            3,
            $images,
            sprintf('Expected 3 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            3,
            $num,
            sprintf('Expected model to have 3 hits, got %d', $num),
        );

        $images = $this->adapter->getImages([$user1, $user3], new Query(), $model);

        $this->assertCount(
            4,
            $images,
            sprintf('Expected 4 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            4,
            $num,
            sprintf('Expected model to have 4 hits, got %d', $num),
        );

        $images = $this->adapter->getImages([$user2, $user3], new Query(), $model);

        $this->assertCount(
            5,
            $images,
            sprintf('Expected 5 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            5,
            $num,
            sprintf('Expected model to have 5 hits, got %d', $num),
        );

        // @see https://github.com/imbo/imbo/issues/552
        $images = $this->adapter->getImages([], new Query(), $model);

        $this->assertCount(
            6,
            $images,
            sprintf('Expected 6 images, got %d', count($images)),
        );

        $num = $model->getHits();

        $this->assertSame(
            6,
            $num,
            sprintf('Expected model to have 6 hits, got %d', $num),
        );
    }

    public function testCanGetNumberOfBytes(): void
    {
        $num = $this->adapter->getNumBytes('user');

        $this->assertSame(
            0,
            $num,
            sprintf('Expected 0 bytes, got %d', $num),
        );

        $this->assertTrue(
            $this->adapter->insertImage('user', 'id', self::getImageModel()),
            'Unable to insert image',
        );

        $num = $this->adapter->getNumBytes('user');

        $this->assertSame(
            1402,
            $num,
            sprintf('Expected 1402 bytes, got %d', $num),
        );

        $this->assertTrue(
            $this->adapter->insertImage('user2', 'id', self::getImageModel()),
            'Unable to insert image',
        );

        $num = $this->adapter->getNumBytes('user2');

        $this->assertSame(
            1402,
            $num,
            sprintf('Expected 1402 bytes, got %d', $num),
        );

        $num = $this->adapter->getNumBytes();

        $this->assertSame(
            2804,
            $num,
            sprintf('Expected 2804 bytes, got %d', $num),
        );
    }

    public function testCanGetNumberOfUsers(): void
    {
        $this->assertTrue(
            $this->adapter->insertImage('user', 'id', self::getImageModel()),
            'Unable to insert image',
        );

        $num = $this->adapter->getNumUsers();

        $this->assertSame(
            1,
            $num,
            sprintf('Expected 1 user, got %d', $num),
        );

        $this->assertTrue(
            $this->adapter->insertImage('user2', 'id', self::getImageModel()),
            'Unable to insert image',
        );

        $num = $this->adapter->getNumUsers();

        $this->assertSame(
            2,
            $num,
            sprintf('Expected 2 users, got %d', $num),
        );
    }

    /**
     * @return array<string,array{sort:array<string>,field:string,values:array<int>|array<string>}>
     */
    public static function getSortData(): array
    {
        return [
            'no sorting' => [
                'sort'   => [],
                'field'  => 'imageIdentifier',
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
                'sort'   => ['size'],
                'field'  => 'size',
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
                'sort'   => ['size:desc'],
                'field'  => 'size',
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
                'sort'   => ['width:asc', 'size:desc'],
                'field'  => 'size',
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
     * @param array<string> $sort
     * @param array<string>|array<int> $values
     */
    #[DataProvider('getSortData')]
    public function testCanSortImages(array $sort, string $field, array $values): void
    {
        $this->insertImages();

        $query = new Query();

        if (!empty($sort)) {
            $query->setSort($sort);
        }

        $images = $this->adapter->getImages(['user'], $query, $this->createStub(Images::class));

        foreach ($images as $i => $image) {
            $this->assertSame(
                $values[$i],
                $image[$field],
                sprintf(
                    'Incorrectly sorted images. Expected "%s" on index %d, got "%s"',
                    (string) $values[$i],
                    $i,
                    (string) $image[$field],
                ),
            );
        }
    }

    public function testThrowsExceptionWhenSortingOnInvalidKey(): void
    {
        $query = new Query();
        $query->setSort(['foo:asc']);

        $this->expectExceptionObject(new DatabaseException(
            'Invalid sort field: foo',
            400,
        ));
        $this->adapter->getImages(['user'], $query, $this->createStub(Images::class));
    }

    public function testCanGetStatus(): void
    {
        $this->assertTrue(
            $this->adapter->getStatus(),
            'Expected status to be true',
        );
    }

    /**
     * @return array<string,array{images:array<Image>,expectedUsers:array<string>}>
     */
    public static function getDataForAllUsers(): array
    {
        $image  = self::getImageModel();
        $image2 = clone $image;
        $image3 = clone $image;
        $image4 = clone $image;
        $image5 = clone $image;
        $image6 = clone $image;

        return [
            'no images' => [
                'images'        => [],
                'expectedUsers' => [],
            ],
            'images with different users' => [
                'images' => [
                    $image->setUser('user1')->setImageIdentifier(uniqid('imbo-', true)),
                    $image2->setUser('user3')->setImageIdentifier(uniqid('imbo-', true)),
                    $image3->setUser('user1')->setImageIdentifier(uniqid('imbo-', true)),
                    $image4->setUser('user2')->setImageIdentifier(uniqid('imbo-', true)),
                    $image5->setUser('user2')->setImageIdentifier(uniqid('imbo-', true)),
                    $image6->setUser('user2')->setImageIdentifier(uniqid('imbo-', true)),
                ],
                'expectedUsers' => ['user1', 'user2', 'user3'],
            ],
        ];
    }

    /**
     * @param array<Image> $images
     * @param array<string> $expectedUsers
     */
    #[DataProvider('getDataForAllUsers')]
    public function testCanGetAllUsers(array $images, array $expectedUsers): void
    {
        foreach ($images as $image) {
            $this->assertTrue(
                $this->adapter->insertImage(
                    (string) $image->getUser(),
                    (string) $image->getImageIdentifier(),
                    $image,
                ),
                'Unable to insert image',
            );
        }

        $this->assertSame(
            $expectedUsers,
            $this->adapter->getAllUsers(),
            'Incorrect list of users',
        );
    }

    public function testUpdatesImageIfDuplicate(): void
    {
        $image = (new Image())
            ->setMimeType('image/png')
            ->setExtension('png')
            ->setWidth(665)
            ->setHeight(463)
            ->setBlob((string) file_get_contents(self::$fixturesDir . '/image.png'))
            ->setAddedDate(new DateTime('@1331852400'))
            ->setUpdatedDate(new DateTime('@1331852400'))
            ->setOriginalChecksum('929db9c5fc3099f7576f5655207eba47');

        $this->assertTrue(
            $this->adapter->insertImage('user', 'image-id', $image),
            'Unable to add image',
        );

        $originalProperties = $this->adapter->getImageProperties('user', 'image-id');

        $this->assertTrue(
            $this->adapter->insertImage('user', 'image-id', $image),
            'Unable to add image',
        );

        $updatedProperties = $this->adapter->getImageProperties('user', 'image-id');

        $this->assertGreaterThan(
            $originalProperties['updated'],
            $updatedProperties['updated'],
            'Updated timestamp should be greater than the original',
        );
    }

    /**
     * Insert some images to test the query functionality
     *
     * All images added is owned by "user", unless $alternateUser is set to true, in which case
     * every other image is owned by "user2".
     *
     * @param bool $alternateUser Whether to alternate between 'user' and 'user2' when inserting
     *                            images
     * @return int[] Returns an array with two elements where the first is the timestamp of when
     *               the first image was added, and the second is the timestamp of when the last
     *               image was added
     */
    private function insertImages(bool $alternateUser = false): array
    {
        $now   = time();
        $start = $now;

        foreach (['image.jpg', 'image.png', 'image1.png', 'image2.png', 'image3.png', 'image4.png'] as $i => $fileName) {
            $path            = self::$fixturesDir . '/' . $fileName;
            $imageIdentifier = (string) md5_file($path);

            if (false === ($info = getimagesize($path))) {
                throw new RuntimeException(sprintf('Unable to get image size for: %s', $path));
            }

            $user = 'user';

            if ($alternateUser && $i % 2 === 0) {
                $user = 'user2';
            }

            $image = (new Image())
                ->setMimeType($info['mime'])
                ->setExtension(substr($fileName, (int) strrpos($fileName, '.') + 1))
                ->setWidth($info[0])
                ->setHeight($info[1])
                ->setBlob((string) file_get_contents($path))
                ->setAddedDate(new DateTime('@' . $now))
                ->setUpdatedDate(new DateTime('@' . $now))
                ->setOriginalChecksum($imageIdentifier);

            $now++;

            $this->assertTrue(
                $this->adapter->insertImage($user, $imageIdentifier, $image),
                'Unable to add image',
            );

            $this->assertTrue(
                $this->adapter->updateMetadata($user, $imageIdentifier, [
                    'key' . $i => 'value' . $i,
                ]),
                'Unable to update metadata',
            );
        }

        // Remove the last increment to get the timestamp for when the last image was added
        $end = $now - 1;

        return [$start, $end];
    }

    /**
     * @param array<mixed> $expected Expected array
     * @param array<mixed> $actual Actual array
     * @param string $message Optional failure message
     */
    private function assertMultidimensionalArraysAreEqual(array $expected, array $actual, string $message = ''): void
    {
        static::assertThat(
            $actual,
            new MultidimensionalArrayIsEqual($expected),
            $message,
        );
    }

    /**
     * @return array<string,array{users:array<string>}>
     */
    public static function getUsers(): array
    {
        return [
            'no users' => [
                'users' => [],
            ],
            'multiple users' => [
                'users' => ['user1', 'user2', 'user3'],
            ],
        ];
    }
}
