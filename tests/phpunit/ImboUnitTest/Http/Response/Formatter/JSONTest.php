<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Http\Response\Formatter;

use Imbo\Http\Response\Formatter\JSON,
    DateTime;

/**
 * @covers Imbo\Http\Response\Formatter\JSON
 * @group unit
 * @group http
 * @group formatters
 */
class JSONTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var JSON
     */
    private $formatter;

    private $dateFormatter;

    /**
     * Set up the formatter
     *
     * @covers Imbo\Http\Response\Formatter\Formatter::__construct
     */
    public function setUp() {
        $this->dateFormatter = $this->getMock('Imbo\Helpers\DateFormatter');
        $this->formatter = new JSON($this->dateFormatter);
    }

    /**
     * Tear down the formatter
     */
    public function tearDown() {
        $this->dateFormatter;
        $this->formatter = null;
    }

    /**
     * @covers Imbo\Http\Response\Formatter\JSON::getContentType
     */
    public function testReturnsCurrectContentType() {
        $this->assertSame('application/json', $this->formatter->getContentType());
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatError
     * @covers Imbo\Http\Response\Formatter\JSON::encode
     */
    public function testCanFormatAnErrorModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->getMock('Imbo\Model\Error');
        $model->expects($this->once())->method('getHttpCode')->will($this->returnValue(404));
        $model->expects($this->once())->method('getErrorMessage')->will($this->returnValue('Public key not found'));
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getImboErrorCode')->will($this->returnValue(100));
        $model->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('identifier'));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['error']['date']);
        $this->assertSame('Public key not found', $data['error']['message']);
        $this->assertSame(404, $data['error']['code']);
        $this->assertSame(100, $data['error']['imboErrorCode']);
        $this->assertSame('identifier', $data['imageIdentifier']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\JSON::formatError
     */
    public function testCanFormatAnErrorModelWhenNoImageIdentifierExists() {
        $date = new DateTime();

        $model = $this->getMock('Imbo\Model\Error');
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(null));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertArrayNotHasKey('imageIdentifier', $data);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatStatus
     */
    public function testCanFormatAStatusModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->getMock('Imbo\Model\Status');
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getDatabaseStatus')->will($this->returnValue(true));
        $model->expects($this->once())->method('getStorageStatus')->will($this->returnValue(false));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['date']);
        $this->assertTrue($data['database']);
        $this->assertFalse($data['storage']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatUser
     */
    public function testCanFormatAUserModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->getMock('Imbo\Model\User');
        $model->expects($this->once())->method('getLastModified')->will($this->returnValue($date));
        $model->expects($this->once())->method('getNumImages')->will($this->returnValue(123));
        $model->expects($this->once())->method('getUserId')->will($this->returnValue('christer'));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame($formattedDate, $data['lastModified']);
        $this->assertSame('christer', $data['user']);
        $this->assertSame(123, $data['numImages']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatImages
     */
    public function testCanFormatAnImagesModel() {
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

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getUser')->will($this->returnValue($user));
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $image->expects($this->once())->method('getChecksum')->will($this->returnValue($checksum));
        $image->expects($this->once())->method('getExtension')->will($this->returnValue($extension));
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue($mimeType));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue($addedDate));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue($updatedDate));
        $image->expects($this->once())->method('getFilesize')->will($this->returnValue($filesize));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue($width));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue($height));
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue($metadata));

        $images = [$image];
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));
        $model->expects($this->once())->method('getHits')->will($this->returnValue(100));
        $model->expects($this->once())->method('getPage')->will($this->returnValue(2));
        $model->expects($this->once())->method('getLimit')->will($this->returnValue(20));
        $model->expects($this->once())->method('getCount')->will($this->returnValue(1));

        $this->dateFormatter->expects($this->any())->method('formatDate')->with($this->isInstanceOf('DateTime'))->will($this->returnValue($formattedDate));

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
     * @covers Imbo\Http\Response\Formatter\JSON::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadataSet() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue(null));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue(new DateTime()));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue(new DateTime()));

        $images = [$image];
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertArrayNotHasKey('metadata', $image);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadata() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue([]));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue(new DateTime()));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue(new DateTime()));

        $images = [$image];
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertCount(1, $data['images']);
        $image = $data['images'][0];

        $this->assertEmpty($image['metadata']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatImages
     */
    public function testCanFormatAnImagesModelWithNoImages() {
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue([]));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertCount(0, $data['images']);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatImages
     */
    public function testCanFormatAnImagesModelWithSomefields() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue(new DateTime()));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue(new DateTime()));

        $images = [$image];
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));
        $fields = ['size', 'width', 'height'];
        $model->expects($this->once())->method('getFields')->will($this->returnValue($fields));

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
     * @covers Imbo\Http\Response\Formatter\JSON::formatMetadataModel
     */
    public function testCanFormatAMetadataModel() {
        $metadata = [
            'some key' => 'some value',
            'some other key' => 'some other value',
        ];
        $model = $this->getMock('Imbo\Model\Metadata');
        $model->expects($this->once())->method('getData')->will($this->returnValue($metadata));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame($data, $metadata);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatMetadataModel
     */
    public function testCanFormatAMetadataModelWithNoMetadata() {
        $model = $this->getMock('Imbo\Model\Metadata');
        $model->expects($this->once())->method('getData')->will($this->returnValue([]));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame([], $data);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatArrayModel
     */
    public function testCanFormatAnArrayModel() {
        $data = [
            'some key' => 'some value',
            'some other key' => 'some other value',
            'nested' => [
                'subkey' => [
                    'subsubkey' => 'some value',
                ],
            ],
        ];
        $model = $this->getMock('Imbo\Model\ArrayModel');
        $model->expects($this->once())->method('getData')->will($this->returnValue($data));

        $json = $this->formatter->format($model);

        $this->assertSame(json_decode($json, true), $data);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatArrayModel
     */
    public function testCanFormatAnEmptyArrayModel() {
        $model = $this->getMock('Imbo\Model\ArrayModel');
        $model->expects($this->once())->method('getData')->will($this->returnValue([]));

        $json = $this->formatter->format($model);

        $data = json_decode($json, true);
        $this->assertSame([], $data);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatListModel
     */
    public function testCanFormatAListModel() {
        $list = [1, 2, 3];
        $container = 'foo';
        $model = $this->getMock('Imbo\Model\ListModel');
        $model->expects($this->once())->method('getList')->will($this->returnValue($list));
        $model->expects($this->once())->method('getContainer')->will($this->returnValue($container));

        $this->assertSame('{"foo":[1,2,3]}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatListModel
     */
    public function testCanFormatAnEmptyListModel() {
        $list = [];
        $container = 'foo';
        $model = $this->getMock('Imbo\Model\ListModel');
        $model->expects($this->once())->method('getList')->will($this->returnValue($list));
        $model->expects($this->once())->method('getContainer')->will($this->returnValue($container));

        $this->assertSame('{"foo":[]}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatGroups
     */
    public function testCanFormatGroupsModel() {
        $groups = ['group', 'othergroup'];
        $count = count($groups);
        $hits = 2;
        $limit = 5;
        $page = 1;

        $model = $this->getMock('Imbo\Model\Groups');
        $model->expects($this->once())->method('getHits')->will($this->returnValue($hits));
        $model->expects($this->once())->method('getPage')->will($this->returnValue($page));
        $model->expects($this->once())->method('getLimit')->will($this->returnValue($limit));
        $model->expects($this->once())->method('getCount')->will($this->returnValue($count));
        $model->expects($this->once())->method('getGroups')->will($this->returnValue($groups));

        $this->assertSame('{"search":{"hits":2,"page":1,"limit":5,"count":2},"groups":["group","othergroup"]}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatGroup
     */
    public function testCanFormatAGroupModel() {
        $name = 'group';
        $resources = [
            'user.get',
            'user.head',
        ];

        $model = $this->getMock('Imbo\Model\Group');
        $model->expects($this->once())->method('getName')->will($this->returnValue($name));
        $model->expects($this->once())->method('getResources')->will($this->returnValue($resources));

        $this->assertSame('{"name":"group","resources":["user.get","user.head"]}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatAccessRule
     */
    public function testCanFormatAnAccessRuleModelWithGroup() {
        $id = 1;
        $users = ['user1', 'user2'];
        $group = 'group';

        $model = $this->getMock('Imbo\Model\AccessRule');
        $model->expects($this->once())->method('getId')->will($this->returnValue($id));
        $model->expects($this->once())->method('getUsers')->will($this->returnValue($users));
        $model->expects($this->once())->method('getGroup')->will($this->returnValue($group));

        $this->assertSame('{"id":1,"users":["user1","user2"],"group":"group"}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatAccessRule
     */
    public function testCanFormatAnAccessRuleModelWithResource() {
        $id = 1;
        $users = ['user1', 'user2'];
        $resources = ['resource1', 'resource2'];

        $model = $this->getMock('Imbo\Model\AccessRule');
        $model->expects($this->once())->method('getId')->will($this->returnValue($id));
        $model->expects($this->once())->method('getUsers')->will($this->returnValue($users));
        $model->expects($this->once())->method('getResources')->will($this->returnValue($resources));

        $this->assertSame('{"id":1,"users":["user1","user2"],"resources":["resource1","resource2"]}', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\JSON::formatAccessRules
     */
    public function testCanFormatAccessRulesModel() {
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
        $model = $this->getMock('Imbo\Model\AccessRules');
        $model->expects($this->once())->method('getRules')->will($this->returnValue($rules));

        $this->assertSame('[{"id":1,"group":"group","users":["user1","user2"]},{"id":2,"resources":["image.get","image.head"],"users":["user3","user4"]}]', $this->formatter->format($model));
    }

    /**
     * Data provider for the stats model
     *
     * @return array[]
     */
    public function getStats() {
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
     * @covers Imbo\Http\Response\Formatter\JSON::formatStats
     */
    public function testCanFormatAStatsModel($images, $users, $bytes, $customStats, $expectedJson) {
        $model = $this->getMock('Imbo\Model\Stats');
        $model->expects($this->once())->method('getNumImages')->will($this->returnValue($images));
        $model->expects($this->once())->method('getNumUsers')->will($this->returnValue($users));
        $model->expects($this->once())->method('getNumBytes')->will($this->returnValue($bytes));
        $model->expects($this->once())->method('getCustomStats')->will($this->returnValue($customStats));

        $this->assertSame(
            $this->formatter->format($model),
            $expectedJson
        );
    }
}
