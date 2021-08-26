<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\Resource;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Model\Groups;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Auth\AccessControl\Adapter\ArrayAdapter
 */
class ArrayAdapterTest extends TestCase {
    /**
     * @covers ::__construct
     * @covers ::validateAccessList
     * @covers ::getUsersForResource
     */
    public function testReturnsCorrectListOfAllowedUsersForResource() : void {
        $accessControl = new ArrayAdapter([
            [
                'publicKey' => 'pubKey1',
                'privateKey' => 'privateKey1',
                'acl' => [[
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['user1', 'user2'],
                ]]
            ],
            [
                'publicKey' => 'pubKey2',
                'privateKey' => 'privateKey2',
                'acl' => [[
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['user2', 'user3', '*'],
                ]]
            ],
            [
                'publicKey' => 'pubKey3',
                'privateKey' => 'privateKey3',
                'acl' => [[
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['user4', 'pubKey3'],
                ]]
            ]
        ]);

        $this->assertEquals(
            ['user1', 'user2'],
            $accessControl->getUsersForResource('pubKey1', Resource::IMAGES_GET)
        );

        $this->assertEquals(
            ['user2', 'user3', '*'],
            $accessControl->getUsersForResource('pubKey2', Resource::IMAGES_GET)
        );

        $this->assertEquals(
            ['pubKey3', 'user4'],
            $accessControl->getUsersForResource('pubKey3', Resource::IMAGES_GET)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::validateAccessList
     * @covers ::getPrivateKey
     * @covers ::getKeysFromAcl
     */
    public function testGetPrivateKey() : void {
        $accessControl = new ArrayAdapter([
            [
                'publicKey' => 'pubKey1',
                'privateKey' => 'privateKey1',
                'acl' => [[
                    'resources' => [Resource::IMAGES_POST],
                    'users' => ['user1'],
                ]]
            ],
            [
                'publicKey' => 'pubKey2',
                'privateKey' => 'privateKey2',
                'acl' => [[
                    'resources' => [Resource::IMAGES_POST],
                    'users' => ['user2'],
                ]]
            ]
        ]);

        $this->assertSame('privateKey1', $accessControl->getPrivateKey('pubKey1'));
        $this->assertSame('privateKey2', $accessControl->getPrivateKey('pubKey2'));
        $this->assertNull($accessControl->getPrivateKey('pubKey3'));
    }

    /**
     * @covers ::__construct
     * @covers ::validateAccessList
     * @covers ::hasAccess
     */
    public function testCanReadResourcesFromGroups() : void {
        $acl = [
            [
                'publicKey'  => 'pubkey',
                'privateKey' => 'privkey',
                'acl' => [
                    [
                        'group' => 'user-stats',
                        'users' => ['user1']
                    ]
                ]
            ]
        ];

        $groups = [
            'user-stats' => [
                Resource::USER_GET,
                Resource::USER_HEAD
            ]
        ];

        $ac = new ArrayAdapter($acl, $groups);

        $this->assertFalse($ac->hasAccess('pubkey', Resource::IMAGES_POST, 'user1'));
        $this->assertFalse($ac->hasAccess('pubkey', Resource::IMAGES_POST));
        $this->assertFalse($ac->hasAccess('pubkey', Resource::USER_GET, 'user2'));
        $this->assertTrue($ac->hasAccess('pubkey', Resource::USER_HEAD, 'user1'));
        $this->assertTrue($ac->hasAccess('pubkey', Resource::USER_GET, 'user1'));
    }

    /**
     * @covers ::__construct
     * @covers ::validateAccessList
     * @covers ::hasAccess
     */
    public function testCanReadResourcesGrantedUsingWildcard() : void {
        $accessControl = new ArrayAdapter([
            [
                'publicKey'  => 'pubkey',
                'privateKey' => 'privkey',
                'acl' => [
                    [
                        'resources' => [Resource::IMAGES_GET],
                        'users' => '*'
                    ]
                ]
            ]
        ]);

        $this->assertTrue($accessControl->hasAccess('pubkey', Resource::IMAGES_GET, 'user1'));
        $this->assertTrue($accessControl->hasAccess('pubkey', Resource::IMAGES_GET, 'user2'));
        $this->assertFalse($accessControl->hasAccess('pubkey', Resource::IMAGES_POST, 'user2'));
    }

    /**
     * @covers ::__construct
     * @covers ::validateAccessList
     */
    public function testThrowsErrorOnDuplicatePublicKey() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Public key declared twice in config: pubkey',
            500
        ));
        new ArrayAdapter([
            ['publicKey'  => 'pubkey', 'privateKey' => 'privkey', 'acl' => []],
            ['publicKey'  => 'pubkey', 'privateKey' => 'privkey', 'acl' => []]
        ]);
    }

    public function getGroupsData() : array {
        return [
            'no groups' => [
                [],
                [],
                new GroupQuery(),
            ],
            'some groups' => [
                ['g1' => [], 'g2' => [], 'g3' => []],
                ['g1' => [], 'g2' => [], 'g3' => []],
                new GroupQuery(),
            ],
            'groups with query object' => [
                ['g1' => [], 'g2' => [], 'g3' => [], 'g4' => [], 'g5' => []],
                ['g3' => [], 'g4' => []],
                (new GroupQuery())
                    ->setPage(2)
                    ->setLimit(2),
            ],
        ];
    }

    /**
     * @dataProvider getGroupsData
     * @covers ::getGroups
     */
    public function testCanGetGroups(array $groups, array $result, GroupQuery $query) : void {
        $numGroups = count($groups);

        $model = $this->createMock(Groups::class);
        $model
            ->expects($this->once())
            ->method('setHits')
            ->with($numGroups);

        $adapter = new ArrayAdapter([], $groups);
        $this->assertSame(array_values($result), array_values($adapter->getGroups($query, $model)));
    }

    public function getGroupsForTest() : array {
        return [
            'no groups' => [
                [], 'group', false,
            ],
            'group exists' => [
                ['group' => [], 'othergroup' => []], 'group', true,
            ],
            'group does not exist' => [
                ['group' => [], 'othergroup' => []], 'somegroup', false,
            ],
        ];
    }

    /**
     * @dataProvider getGroupsForTest
     * @covers ::groupExists
     */
    public function testCanCheckIfGroupExists(array $groups, string $group, bool $exists) : void {
        $adapter = new ArrayAdapter([], $groups);
        $this->assertSame($exists, $adapter->groupExists($group));
    }

    /**
     * @covers ::publicKeyExists
     */
    public function testPublicKeyExists() : void {
        $adapter = new ArrayAdapter([
            [
                'publicKey' => 'pubKey1',
                'privateKey' => 'privateKey1',
                'acl' => [[
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['user1', 'user2'],
                ]]
            ],
            [
                'publicKey' => 'pubKey2',
                'privateKey' => 'privateKey2',
                'acl' => [[
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['user2', 'user3', '*'],
                ]]
            ]
        ]);

        $this->assertTrue($adapter->publicKeyExists('pubKey1'));
        $this->assertTrue($adapter->publicKeyExists('pubKey2'));
        $this->assertFalse($adapter->publicKeyExists('pubKey3'));
    }

    public function getAccessRules() : array {
        $acl = [
            [
                'id' => 1,
                'publicKey' => 'pubKey1',
                'privateKey' => 'privateKey1',
                'acl' => [[
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['user1', 'user2'],
                ]]
            ],
            [
                'id' => 2,
                'publicKey' => 'pubKey2',
                'privateKey' => 'privateKey2',
                'acl' => [[
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['user2', 'user3', '*'],
                ]]
            ],
        ];

        return [
            'access rule exists' => [
                'acl' => $acl,
                'publicKey' => 'pubKey1',
                'ruleId' => 1,
                'rule' => [
                    'id' => 1,
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['user1', 'user2'],
                ],
            ],
            'no access rules' => [
                'acl' => [],
                'publicKey' => 'key',
                'ruleId' => 1,
                'rule' => null,
            ],
            'access rule that does not exist' => [
                'acl' => $acl,
                'publicKey' => 'pubKey1',
                'ruleId' => 2,
                'rule' => null,
            ],
        ];
    }

    /**
     * @dataProvider getAccessRules
     * @covers ::getAccessRule
     */
    public function testGetAccessRule(array $acl, string $publicKey, int $ruleId, ?array $rule) : void {
        $adapter = new ArrayAdapter($acl);
        $this->assertSame($rule, $adapter->getAccessRule($publicKey, $ruleId));
    }

    /**
     * @testWith [[], "some-group", null]
     *           [{"foo": {"some": "data"}, "some-group": {"other": "data"}}, "some-group", {"other": "data"}]
     * @covers ::getGroup
     */
    public function testCanGetGroup(array $groups, string $group, $result): void {
        $adapter = new ArrayAdapter([], $groups);
        $this->assertSame($result, $adapter->getGroup($group));
    }

    public function getDataForAccessListTest(): array {
        return [
            'no acls' => [
                'acl' => [],
                'publicKey' => 'some-public-key',
                'result' => [],
            ],
            'no matching keys' => [
                'acl' => [
                    [
                        'publicKey' => 'key',
                        'privateKey' => 'private1',
                        'acl' => [[
                            'foo' => 'bar',
                        ]],
                    ],
                    [
                        'publicKey' => 'other-key',
                        'privateKey' => 'private2',
                        'acl' => [[
                            'foo' => 'bar',
                        ]],
                    ],

                ],
                'publicKey' => 'some-public-key',
                'result' => [],
            ],
            'match' => [
                'acl' => [
                    [
                        'publicKey' => 'key',
                        'privateKey' => 'private1',
                        'acl' => [[
                            'foo' => 'bar',
                        ]],
                    ],
                    [
                        'publicKey' => 'other-key',
                        'privateKey' => 'private2',
                        'acl' => [[
                            'foo' => 'bar',
                        ]],
                    ],

                ],
                'publicKey' => 'other-key',
                'result' => [[
                    'id' => 1,
                    'foo' => 'bar',
                ]],
            ],
        ];
    }

    /**
     * @dataProvider getDataForAccessListTest
     * @covers ::getAccessListForPublicKey
     */
    public function testCanGetAccessListForPublicKey(array $acl, string $publicKey, array $result): void {
        $adapter = new ArrayAdapter($acl, []);
        $this->assertSame($result, $adapter->getAccessListForPublicKey($publicKey));
    }

    /**
     * @covers ::hasAccess
     */
    public function testThrowsExceptionWhenMissingUsersFromAcl(): void {
        $adapter = new ArrayAdapter([[
            'publicKey' => 'public-key',
            'privateKey' => 'some-private-key',
            'acl' => [
                ['foo' => 'bar']
            ],
        ]]);

        $this->expectExceptionObject(new InvalidArgumentException(
            'Missing property "users" in access rule',
            500,
        ));

        $adapter->hasAccess('public-key', 'resource');
    }

    /**
     * @covers ::hasAccess
     */
    public function testThrowsExceptionWhenGroupIsNotDefined(): void {
        $acl = [
            [
                'publicKey'  => 'pubkey',
                'privateKey' => 'privkey',
                'acl' => [
                    [
                        'group' => 'user-stats',
                        'users' => ['user1']
                    ]
                ]
            ]
        ];

        $adapter = new ArrayAdapter($acl, []);

        $this->expectExceptionObject(new InvalidArgumentException(
            'Group "user-stats" is not defined',
            500,
        ));

        $this->assertFalse($adapter->hasAccess('pubkey', Resource::IMAGES_GET, 'user1'));
    }
}
