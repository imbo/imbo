<?php declare(strict_types=1);
namespace Imbo\Http\Response\Formatter;

use DateTime;
use Imbo\Helpers\DateFormatter;
use Imbo\Http\Response\Response;
use Imbo\Model\AccessRule;
use Imbo\Model\AccessRules;
use Imbo\Model\ArrayModel;
use Imbo\Model\Error;
use Imbo\Model\Group;
use Imbo\Model\Groups;
use Imbo\Model\Image;
use Imbo\Model\Images;
use Imbo\Model\Metadata;
use Imbo\Model\Stats;
use Imbo\Model\Status;
use Imbo\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Http\Response\Formatter\JSON
 */
class JSONTest extends TestCase
{
    private $formatter;

    private $dateFormatter;

    public function setUp(): void
    {
        $this->dateFormatter = $this->createMock(DateFormatter::class);
        $this->formatter = new JSON($this->dateFormatter);
    }

    /**
     * @covers ::getContentType
     */
    public function testReturnsCurrectContentType(): void
    {
        $this->assertSame('application/json', $this->formatter->getContentType());
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatError
     * @covers ::encode
     */
    public function testCanFormatAnErrorModel(): void
    {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->createConfiguredMock(Error::class, [
            'getHttpCode' => Response::HTTP_NOT_FOUND,
            'getErrorMessage' => 'Public key not found',
            'getDate' => $date,
            'getImboErrorCode' => 100,
            'getImageIdentifier' => 'identifier',
        ]);

        $this->dateFormatter
            ->expects($this->once())
            ->method('formatDate')
            ->with($date)
            ->willReturn($formattedDate);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['error']['date']);
        $this->assertSame('Public key not found', $data['error']['message']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $data['error']['code']);
        $this->assertSame(100, $data['error']['imboErrorCode']);
        $this->assertSame('identifier', $data['imageIdentifier']);
    }

    /**
     * @covers ::formatError
     */
    public function testCanFormatAnErrorModelWhenNoImageIdentifierExists(): void
    {
        $date = new DateTime();

        $model = $this->createConfiguredMock(Error::class, [
            'getDate' => $date,
            'getImageIdentifier' => null,
        ]);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertArrayNotHasKey('imageIdentifier', $data);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatStatus
     */
    public function testCanFormatAStatusModel(): void
    {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->createConfiguredMock(Status::class, [
            'getDate' => $date,
            'getDatabaseStatus' => true,
            'getStorageStatus' => false,
        ]);

        $this->dateFormatter
            ->expects($this->once())
            ->method('formatDate')
            ->with($date)
            ->willReturn($formattedDate);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['date']);
        $this->assertTrue($data['database']);
        $this->assertFalse($data['storage']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatUser
     */
    public function testCanFormatAUserModel(): void
    {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->createConfiguredMock(User::class, [
            'getLastModified' => $date,
            'getNumImages' => 123,
            'getUserId' => 'christer',
        ]);

        $this->dateFormatter
            ->expects($this->once())
            ->method('formatDate')
            ->with($date)
            ->willReturn($formattedDate);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['lastModified']);
        $this->assertSame('christer', $data['user']);
        $this->assertSame(123, $data['numImages']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatImages
     */
    public function testCanFormatAnImagesModel(): void
    {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';

        $date = new DateTime();

        $addedDate = $date;
        $updatedDate = $date;
        $user = 'christer';
        $imageIdentifier = 'identifier';
        $checksum = 'checksum';
        $extension = 'png';
        $mimeType = 'image/png';
        $filesize = 123123;
        $width = 800;
        $height = 600;
        $metadata = [
            'some key' => 'some value',
            'some other key' => 'some other value',
        ];

        $image = $this->createConfiguredMock(Image::class, [
            'getUser' => $user,
            'getImageIdentifier' => $imageIdentifier,
            'getChecksum' => $checksum,
            'getExtension' => $extension,
            'getMimeType' => $mimeType,
            'getAddedDate' => $addedDate,
            'getUpdatedDate' => $updatedDate,
            'getFilesize' => $filesize,
            'getWidth' => $width,
            'getHeight' => $height,
            'getMetadata' => $metadata,
        ]);

        $images = [$image];
        $model = $this->createConfiguredMock(Images::class, [
            'getImages' => $images,
            'getHits' => 100,
            'getPage' => 2,
            'getLimit' => 20,
            'getCount' => 1,
        ]);

        $this->dateFormatter
            ->expects($this->any())
            ->method('formatDate')
            ->with($this->isInstanceOf('DateTime'))
            ->willReturn($formattedDate);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame(['hits' => 100, 'page' => 2, 'limit' => 20, 'count' => 1], $data['search']);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertSame($formattedDate, $image['added']);
        $this->assertSame($formattedDate, $image['updated']);
        $this->assertSame($user, $image['user']);
        $this->assertSame($filesize, $image['size']);
        $this->assertSame($width, $image['width']);
        $this->assertSame($height, $image['height']);
        $this->assertSame($imageIdentifier, $image['imageIdentifier']);
        $this->assertSame($checksum, $image['checksum']);
        $this->assertSame($extension, $image['extension']);
        $this->assertSame($mimeType, $image['mime']);
        $this->assertSame($metadata, $image['metadata']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadataSet(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getMetadata' => [],
            'getAddedDate' => new DateTime(),
            'getUpdatedDate' => new DateTime(),
        ]);

        $images = [$image];
        $model = $this->createMock(Images::class);
        $model
            ->expects($this->once())
            ->method('getImages')
            ->willReturn($images);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertSame([], $image['metadata']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadata(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getMetadata' => [],
            'getAddedDate' => new DateTime(),
            'getUpdatedDate' => new DateTime(),
        ]);

        $images = [$image];
        $model = $this->createMock(Images::class);
        $model
            ->expects($this->once())
            ->method('getImages')
            ->willReturn($images);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertEmpty($image['metadata']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatImages
     */
    public function testCanFormatAnImagesModelWithNoImages(): void
    {
        $model = $this->createMock(Images::class);
        $model
            ->expects($this->once())
            ->method('getImages')
            ->willReturn([]);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertCount(0, $data['images']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatImages
     */
    public function testCanFormatAnImagesModelWithSomefields(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getAddedDate' => new DateTime(),
            'getUpdatedDate' => new DateTime(),
        ]);

        $images = [$image];
        $model = $this->createMock(Images::class);
        $model
            ->expects($this->once())
            ->method('getImages')
            ->willReturn($images);

        $fields = ['size', 'width', 'height'];
        $model
            ->expects($this->once())
            ->method('getFields')
            ->willReturn($fields);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertArrayHasKey('size', $image);
        $this->assertArrayHasKey('width', $image);
        $this->assertArrayHasKey('height', $image);

        $this->assertSameSize($fields, $image, 'Image array has to many keys');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatMetadataModel
     */
    public function testCanFormatAMetadataModel(): void
    {
        $metadata = [
            'some key' => 'some value',
            'some other key' => 'some other value',
        ];
        $model = $this->createConfiguredMock(Metadata::class, [
            'getData' => $metadata,
        ]);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame($data, $metadata);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatMetadataModel
     */
    public function testCanFormatAMetadataModelWithNoMetadata(): void
    {
        $model = $this->createConfiguredMock(Metadata::class, [
            'getData' => [],
        ]);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame([], $data);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatArrayModel
     */
    public function testCanFormatAnArrayModel(): void
    {
        $data = [
            'some key' => 'some value',
            'some other key' => 'some other value',
            'nested' => [
                'subkey' => [
                    'subsubkey' => 'some value',
                ],
            ],
        ];
        $model = $this->createConfiguredMock(ArrayModel::class, [
            'getData' => $data,
        ]);

        $json = $this->formatter->format($model);

        $this->assertSame(json_decode($json, true), $data);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatArrayModel
     */
    public function testCanFormatAnEmptyArrayModel(): void
    {
        $model = $this->createConfiguredMock(ArrayModel::class, [
            'getData' => [],
        ]);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame([], $data);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatGroups
     */
    public function testCanFormatGroupsModel(): void
    {
        $groups = ['group', 'othergroup'];
        $count = count($groups);
        $hits = 2;
        $limit = 5;
        $page = 1;

        $model = $this->createConfiguredMock(Groups::class, [
            'getHits' => $hits,
            'getPage' => $page,
            'getLimit' => $limit,
            'getCount' => $count,
            'getGroups' => $groups,
        ]);

        $this->assertSame('{"search":{"hits":2,"page":1,"limit":5,"count":2},"groups":["group","othergroup"]}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatGroup
     */
    public function testCanFormatAGroupModel(): void
    {
        $name = 'group';
        $resources = [
            'user.get',
            'user.head',
        ];

        $model = $this->createConfiguredMock(Group::class, [
            'getName' => $name,
            'getResources' => $resources,
        ]);

        $this->assertSame('{"name":"group","resources":["user.get","user.head"]}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatAccessRule
     */
    public function testCanFormatAnAccessRuleModelWithGroup(): void
    {
        $id = 1;
        $users = ['user1', 'user2'];
        $group = 'group';

        $model = $this->createConfiguredMock(AccessRule::class, [
            'getId' => $id,
            'getUsers' => $users,
            'getGroup' => $group,
        ]);

        $this->assertSame('{"id":1,"users":["user1","user2"],"group":"group"}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatAccessRule
     */
    public function testCanFormatAnAccessRuleModelWithResource(): void
    {
        $id = 1;
        $users = ['user1', 'user2'];
        $resources = ['resource1', 'resource2'];

        $model = $this->createConfiguredMock(AccessRule::class, [
            'getId' => $id,
            'getUsers' => $users,
            'getResources' => $resources,
        ]);

        $this->assertSame('{"id":1,"users":["user1","user2"],"resources":["resource1","resource2"]}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatAccessRules
     */
    public function testCanFormatAccessRulesModel(): void
    {
        $rules = [
            [
                'id' => 1,
                'group' => 'group',
                'users' => ['user1', 'user2'],
            ],
            [
                'id' => 2,
                'resources' => ['image.get', 'image.head'],
                'users' => ['user3', 'user4'],
            ],
        ];
        $model = $this->createConfiguredMock(AccessRules::class, [
            'getRules' => $rules,
        ]);

        $this->assertSame('[{"id":1,"group":"group","users":["user1","user2"]},{"id":2,"resources":["image.get","image.head"],"users":["user3","user4"]}]', $this->formatter->format($model));
    }

    public function getStats(): array
    {
        return [
            'no-custom-stats' => [
                1,
                2,
                3,
                [],
                '{"numImages":1,"numUsers":2,"numBytes":3,"custom":{}}',
            ],
            'custom-stats' => [
                4,
                5,
                6,
                [
                    'foo' => 'bar',
                    'bar' => [
                        'foobar' => 'foooo',
                    ],
                ],
                '{"numImages":4,"numUsers":5,"numBytes":6,"custom":{"foo":"bar","bar":{"foobar":"foooo"}}}',
            ],
        ];
    }

    /**
     * @dataProvider getStats
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers ::formatStats
     */
    public function testCanFormatAStatsModel($images, $users, $bytes, $customStats, $expectedJson): void
    {
        $model = $this->createConfiguredMock(Stats::class, [
            'getNumImages' => $images,
            'getNumUsers' => $users,
            'getNumBytes' => $bytes,
            'getCustomStats' => $customStats,
        ]);

        $this->assertSame(
            $this->formatter->format($model),
            $expectedJson,
        );
    }
}
