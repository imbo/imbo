<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Auth;

use Imbo\Auth\AccessControl\ArrayAdapter,
    Imbo\Auth\AccessControl\AccessControlInterface as ACI,
    Imbo\Auth\AccessControl\UserQuery;

/**
 * @covers Imbo\Auth\AccessControl\ArrayAdapter
 * @group unit
 */
class ArrayAdapterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @dataProvider getAuthConfig
     */
    public function testCanSetKeysFromLegacyConfig(array $users, $publicKey, $privateKey) {
        $accessControl = new ArrayAdapter();
        $accessControl->setAccessListFromAuth($users);

        $this->assertSame($privateKey, $accessControl->getPrivateKey($publicKey));
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage A public key can only have a single private key (as of 2.0.0)
     */
    public function testThrowsOnMultiplePrivateKeysPerPublicKey() {
        $accessControl = new ArrayAdapter();
        $accessControl->setAccessListFromAuth([
            'publicKey' => ['key1', 'key2']
        ]);
    }

    public function testLegacyConfigKeysHaveWriteAccess() {
        $accessControl = new ArrayAdapter();
        $accessControl->setAccessListFromAuth([
            'publicKey' => 'privateKey',
        ]);

        $this->assertTrue(
            $accessControl->hasAccess(
                'publicKey',
                ACI::RESOURCE_IMAGES_POST
            )
        );
    }

    public function testUserExists() {
        $accessControl = new ArrayAdapter([
            [
                'publicKey' => 'aPubKey',
                'privateKey' => 'privateKey',
                'acl' => [
                    [
                        'resources' => [ACI::RESOURCE_IMAGES_POST],
                        'users' => ['user1']
                    ]
                ]
            ]
        ]);

        $this->assertTrue($accessControl->userExists('user1'));
        $this->assertFalse($accessControl->userExists('user2'));
    }

    public function testGetPrivateKey() {
        $accessControl = new ArrayAdapter([
            [
                'publicKey' => 'pubKey1',
                'privateKey' => 'privateKey1',
                'acl' => [[
                    'resources' => [ACI::RESOURCE_IMAGES_POST],
                    'users' => ['user1'],
                ]]
            ],
            [
                'publicKey' => 'pubKey2',
                'privateKey' => 'privateKey2',
                'acl' => [[
                    'resources' => [ACI::RESOURCE_IMAGES_POST],
                    'users' => ['user2'],
                ]]
            ]
        ]);

        $this->assertSame('privateKey1', $accessControl->getPrivateKey('pubKey1'));
        $this->assertSame('privateKey2', $accessControl->getPrivateKey('pubKey2'));
    }

    /**
     * @dataProvider getAclAndQuery
     */
    public function testCanGetUsers(array $acl, UserQuery $query, array $expectedUsers = []) {
        $accessControl = new ArrayAdapter($acl);
        $this->assertSame($expectedUsers, $accessControl->getUsers($query));
    }

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
                ACI::RESOURCE_USER_GET,
                ACI::RESOURCE_USER_HEAD
            ]
        ];

        $ac = new ArrayAdapter($acl, $groups);

        $this->assertFalse($ac->hasAccess('pubkey', ACI::RESOURCE_IMAGES_POST, 'user1'));
        $this->assertFalse($ac->hasAccess('pubkey', ACI::RESOURCE_IMAGES_POST));
        $this->assertFalse($ac->hasAccess('pubkey', ACI::RESOURCE_USER_GET, 'user2'));
        $this->assertTrue($ac->hasAccess('pubkey', ACI::RESOURCE_USER_HEAD, 'user1'));
        $this->assertTrue($ac->hasAccess('pubkey', ACI::RESOURCE_USER_GET, 'user1'));
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
     * Data provider for user querying
     *
     * @return array[]
     */
    public function getAclAndQuery() {
        $acl = [
            ['publicKey' => 'pubKey1', 'privateKey' => '', 'acl' => [['users' => ['user1', 'user2']]]],
            ['publicKey' => 'pubKey2', 'privateKey' => '', 'acl' => [['users' => ['user1']]]],
            ['publicKey' => 'pubKey3', 'privateKey' => '', 'acl' => [['users' => ['user3']]]],
            ['publicKey' => 'pubKey4', 'privateKey' => '', 'acl' => [['users' => []]]],
            ['publicKey' => 'pubKey5', 'privateKey' => '', 'acl' => [['users' => ['user4', 'user5']]]],
            ['publicKey' => 'pubKey6', 'privateKey' => '', 'acl' => [['users' => ['user6']]]]
        ];

        return [
            'empty query' => [
                $acl,
                new UserQuery(),
                ['user1', 'user2', 'user3', 'user4', 'user5', 'user6'],
            ],
            'query with limit and offset' => [
                $acl,
                (new UserQuery())->limit(2)->offset(3),
                [
                    'user4', 'user5',
                ],
            ],
            'query with limit out of bounds' => [
                $acl,
                (new UserQuery())->limit(4)->offset(5),
                [
                    'user6',
                ],
            ],
            'query with offset out of bounds' => [
                $acl,
                (new UserQuery())->limit(4)->offset(10),
                [],
            ],
        ];
    }
}
