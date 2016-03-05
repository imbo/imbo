<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter,
    Imbo\Auth\AccessControl\GroupQuery,
    Imbo\Resource;

/**
 * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter
 * @group unit
 */
class ArrayAdapterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::__construct
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::validateAccessList
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::getUsersForResource
     */
    public function testReturnsCorrectListOfAllowedUsersForResource() {
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
            ]
        ]);

        $this->assertEquals(
            ['user1', 'user2'],
            $accessControl->getUsersForResource('pubKey1', Resource::IMAGES_GET)
        );

        $this->assertEquals(
            ['user1', 'user2'],
            $accessControl->getUsersForResource('pubKey1', Resource::IMAGES_GET)
        );
    }

    /**
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::__construct
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::validateAccessList
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::getPrivateKey
     */
    public function testGetPrivateKey() {
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
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::__construct
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::validateAccessList
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::hasAccess
     */
    public function testCanReadResourcesFromGroups() {
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
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::__construct
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::validateAccessList
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::hasAccess
     */
    public function testCanReadResourcesGrantedUsingWildcard() {
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
     * Data provider for testing the legacy auth compatibility
     *
     * @return array
     */
    public function getAuthConfig() {
        $users = [
            'publicKey1' => 'key1',
            'publicKey2' => 'key2',
        ];

        return [
            'no public keys exists' => [[], 'public', null],
            'public key exists' => [$users, 'publicKey2', 'key2'],
            'public key does not exist' => [$users, 'publicKey3', null],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Public key declared twice in config: pubkey
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::__construct
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::validateAccessList
     */
    public function testThrowsErrorOnDuplicatePublicKey() {
        $accessControl = new ArrayAdapter([
            ['publicKey'  => 'pubkey', 'privateKey' => 'privkey', 'acl' => []],
            ['publicKey'  => 'pubkey', 'privateKey' => 'privkey', 'acl' => []]
        ]);
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getGroupsData() {
        $query = new GroupQuery();
        $query->page(2)->limit(2);

        return [
            'no groups' => [
                [],
                [],
            ],
            'some groups' => [
                ['g1' => [], 'g2' => [], 'g3' => []],
                ['g1' => [], 'g2' => [], 'g3' => []],
            ],
            'groups with query object' => [
                ['g1' => [], 'g2' => [], 'g3' => [], 'g4' => [], 'g5' => []],
                ['g3' => [], 'g4' => []],
                $query
            ],
        ];
    }

    /**
     * @dataProvider getGroupsData
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::getGroups
     */
    public function testCanGetGroups(array $groups, array $result, $query = null) {
        $numGroups = count($groups);

        $model = $this->getMock('Imbo\Model\Groups');
        $model->expects($this->once())->method('setHits')->with($numGroups);

        $adapter = new ArrayAdapter([], $groups);
        $this->assertSame(array_values($result), array_values($adapter->getGroups($query, $model)));
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getGroups() {
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
     * @dataProvider getGroups
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::groupExists
     */
    public function testCanCheckIfGroupExists($groups, $group, $exists) {
        $adapter = new ArrayAdapter([], $groups);
        $this->assertSame($exists, $adapter->groupExists($group));
    }

    /**
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::publicKeyExists
     */
    public function testPublicKeyExists() {
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

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getAccessRules() {
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
     * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter::getAccessRule
     */
    public function testGetAccessRule($acl, $publicKey, $ruleId, $rule) {
        $adapter = new ArrayAdapter($acl);
        $this->assertSame($rule, $adapter->getAccessRule($publicKey, $ruleId));
    }
}
