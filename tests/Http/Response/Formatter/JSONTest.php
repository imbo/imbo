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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(JSON::class)]
#[CoversClass(Formatter::class)]
class JSONTest extends TestCase
{
    private JSON $formatter;
    private DateFormatter&MockObject $dateFormatter;

    public function setUp(): void
    {
        $this->dateFormatter = $this->createMock(DateFormatter::class);
        $this->formatter = new JSON($this->dateFormatter);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testReturnsCurrectContentType(): void
    {
        $this->assertSame('application/json', $this->formatter->getContentType());
    }

    public function testCanFormatAnErrorModel(): void
    {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->createConfiguredStub(Error::class, [
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

        /** @var array{error:array{date:string,message:string,code:int,imboErrorCode:int},imageIdentifier:string} */
        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['error']['date']);
        $this->assertSame('Public key not found', $data['error']['message']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $data['error']['code']);
        $this->assertSame(100, $data['error']['imboErrorCode']);
        $this->assertSame('identifier', $data['imageIdentifier']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAnErrorModelWhenNoImageIdentifierExists(): void
    {
        $date = new DateTime();

        $model = $this->createConfiguredStub(Error::class, [
            'getDate' => $date,
            'getImageIdentifier' => null,
        ]);

        $json = $this->formatter->format($model);

        /** @var array{imageIdentifier:string} */
        $data = json_decode($json, true);
        $this->assertArrayNotHasKey('imageIdentifier', $data);
    }

    public function testCanFormatAStatusModel(): void
    {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->createConfiguredStub(Status::class, [
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

        /** @var array{date:string,database:bool,storage:bool} */
        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['date']);
        $this->assertTrue($data['database']);
        $this->assertFalse($data['storage']);
    }

    public function testCanFormatAUserModel(): void
    {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->createConfiguredStub(User::class, [
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

        /** @var array{lastModified:string,user:string,numImages:int} */
        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['lastModified']);
        $this->assertSame('christer', $data['user']);
        $this->assertSame(123, $data['numImages']);
    }

    #[AllowMockObjectsWithoutExpectations]
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

        $image = $this->createConfiguredStub(Image::class, [
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
        $model = $this->createConfiguredStub(Images::class, [
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

        /**
         * @var array{
         *   search:array{
         *     hits:int,
         *     page:int,
         *     limit:int,
         *     count:int
         *   },
         *   images:array<array{
         *     added:string,
         *     updated:string,
         *     user:string,
         *     size:int,
         *     width:int,
         *     height:int,
         *     imageIdentifier:string,
         *     checksum:string,
         *     extension:string,
         *     mime:string,
         *     metadata:array
         *   }>
         * }
         */
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

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAnImagesModelWithNoMetadataSet(): void
    {
        $image = $this->createConfiguredStub(Image::class, [
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

        /** @var array{images:array<array{metadata:array}>} */
        $data = json_decode($json, true);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertSame([], $image['metadata']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAnImagesModelWithNoMetadata(): void
    {
        $image = $this->createConfiguredStub(Image::class, [
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

        /** @var array{images:array<array{metadata:array}>} */
        $data = json_decode($json, true);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertEmpty($image['metadata']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAnImagesModelWithNoImages(): void
    {
        $model = $this->createMock(Images::class);
        $model
            ->expects($this->once())
            ->method('getImages')
            ->willReturn([]);

        $json = $this->formatter->format($model);

        /** @var array{images:array} */
        $data = json_decode($json, true);
        $this->assertCount(0, $data['images']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAnImagesModelWithSomefields(): void
    {
        $image = $this->createConfiguredStub(Image::class, [
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

        /** @var array{images:array<array<string,mixed>>} */
        $data = json_decode($json, true);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertArrayHasKey('size', $image);
        $this->assertArrayHasKey('width', $image);
        $this->assertArrayHasKey('height', $image);

        $this->assertSameSize($fields, $image, 'Image array has to many keys');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAMetadataModel(): void
    {
        $metadata = [
            'some key' => 'some value',
            'some other key' => 'some other value',
        ];
        $model = $this->createConfiguredStub(Metadata::class, [
            'getData' => $metadata,
        ]);

        $json = $this->formatter->format($model);

        /** @var array<string,string> */
        $data = json_decode($json, true);
        $this->assertSame($data, $metadata);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAMetadataModelWithNoMetadata(): void
    {
        $model = $this->createConfiguredStub(Metadata::class, [
            'getData' => [],
        ]);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame([], $data);
    }

    #[AllowMockObjectsWithoutExpectations]
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
        $model = $this->createConfiguredStub(ArrayModel::class, [
            'getData' => $data,
        ]);

        $json = $this->formatter->format($model);

        $this->assertSame(json_decode($json, true), $data);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAnEmptyArrayModel(): void
    {
        $model = $this->createConfiguredStub(ArrayModel::class, [
            'getData' => [],
        ]);

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame([], $data);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatGroupsModel(): void
    {
        $groups = ['group', 'othergroup'];
        $count = count($groups);
        $hits = 2;
        $limit = 5;
        $page = 1;

        $model = $this->createConfiguredStub(Groups::class, [
            'getHits' => $hits,
            'getPage' => $page,
            'getLimit' => $limit,
            'getCount' => $count,
            'getGroups' => $groups,
        ]);

        $this->assertSame('{"search":{"hits":2,"page":1,"limit":5,"count":2},"groups":["group","othergroup"]}', $this->formatter->format($model));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAGroupModel(): void
    {
        $name = 'group';
        $resources = [
            'user.get',
            'user.head',
        ];

        $model = $this->createConfiguredStub(Group::class, [
            'getName' => $name,
            'getResources' => $resources,
        ]);

        $this->assertSame('{"name":"group","resources":["user.get","user.head"]}', $this->formatter->format($model));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAnAccessRuleModelWithGroup(): void
    {
        $id = 1;
        $users = ['user1', 'user2'];
        $group = 'group';

        $model = $this->createConfiguredStub(AccessRule::class, [
            'getId' => $id,
            'getUsers' => $users,
            'getGroup' => $group,
        ]);

        $this->assertSame('{"id":1,"users":["user1","user2"],"group":"group"}', $this->formatter->format($model));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAnAccessRuleModelWithResource(): void
    {
        $id = 1;
        $users = ['user1', 'user2'];
        $resources = ['resource1', 'resource2'];

        $model = $this->createConfiguredStub(AccessRule::class, [
            'getId' => $id,
            'getUsers' => $users,
            'getResources' => $resources,
        ]);

        $this->assertSame('{"id":1,"users":["user1","user2"],"resources":["resource1","resource2"]}', $this->formatter->format($model));
    }

    #[AllowMockObjectsWithoutExpectations]
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
        $model = $this->createConfiguredStub(AccessRules::class, [
            'getRules' => $rules,
        ]);

        $this->assertSame('[{"id":1,"group":"group","users":["user1","user2"]},{"id":2,"resources":["image.get","image.head"],"users":["user3","user4"]}]', $this->formatter->format($model));
    }

    #[DataProvider('getStats')]
    #[AllowMockObjectsWithoutExpectations]
    public function testCanFormatAStatsModel(int $images, int $users, int $bytes, array $customStats, string $expectedJson): void
    {
        $model = $this->createConfiguredStub(Stats::class, [
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

    /**
     * @return array<string,array{images:int,users:int,bytes:int,customStats:array,expectedJson:string}>
     */
    public static function getStats(): array
    {
        return [
            'no-custom-stats' => [
                'images' => 1,
                'users' => 2,
                'bytes' => 3,
                'customStats' => [],
                'expectedJson' => '{"numImages":1,"numUsers":2,"numBytes":3,"custom":{}}',
            ],
            'custom-stats' => [
                'images' => 4,
                'users' => 5,
                'bytes' => 6,
                'customStats' => [
                    'foo' => 'bar',
                    'bar' => [
                        'foobar' => 'foooo',
                    ],
                ],
                'expectedJson' => '{"numImages":4,"numUsers":5,"numBytes":6,"custom":{"foo":"bar","bar":{"foobar":"foooo"}}}',
            ],
        ];
    }
}
