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

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter,
    Imbo\Auth\AccessControl\Adapter\AdapterInterface as ACI,
    Imbo\Auth\AccessControl\UserQuery;

/**
 * @covers Imbo\Auth\AccessControl\Adapter\ArrayAdapter
 * @group unit
 */
class ArrayAdapterTest extends \PHPUnit_Framework_TestCase {
    public function testReturnsCorrectListOfAllowedUsersForResource() {
        $accessControl = new ArrayAdapter([
            [
                'publicKey' => 'pubKey1',
                'privateKey' => 'privateKey1',
                'acl' => [[
                    'resources' => [ACI::RESOURCE_IMAGES_GET],
                    'users' => ['user1', 'user2'],
                ]]
            ],
            [
                'publicKey' => 'pubKey2',
                'privateKey' => 'privateKey2',
                'acl' => [[
                    'resources' => [ACI::RESOURCE_IMAGES_GET],
                    'users' => ['user2', 'user3', '*'],
                ]]
            ]
        ]);

        $this->assertEquals(
            ['user1', 'user2'],
            $accessControl->getUsersForResource('pubKey1', ACI::RESOURCE_IMAGES_GET)
        );

        $this->assertEquals(
            ['user1', 'user2'],
            $accessControl->getUsersForResource('pubKey1', ACI::RESOURCE_IMAGES_GET)
        );
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

    public function testCanReadResourcesGrantedUsingWildcard() {
        $accessControl = new ArrayAdapter([
            [
                'publicKey'  => 'pubkey',
                'privateKey' => 'privkey',
                'acl' => [
                    [
                        'resources' => [ACI::RESOURCE_IMAGES_GET],
                        'users' => '*'
                    ]
                ]
            ]
        ]);

        $this->assertTrue($accessControl->hasAccess('pubkey', ACI::RESOURCE_IMAGES_GET, 'user1'));
        $this->assertTrue($accessControl->hasAccess('pubkey', ACI::RESOURCE_IMAGES_GET, 'user2'));
        $this->assertFalse($accessControl->hasAccess('pubkey', ACI::RESOURCE_IMAGES_POST, 'user2'));
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
}
