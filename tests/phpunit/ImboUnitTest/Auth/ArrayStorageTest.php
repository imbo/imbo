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

use Imbo\Auth\ArrayStorage,
    Imbo\Auth\UserLookupInterface,
    Imbo\Auth\UserLookup\Query;

/**
 * @covers Imbo\Auth\ArrayStorage
 * @group unit
 */
class ArrayStorageTest extends \PHPUnit_Framework_TestCase {
    public function getUsers() {
        $users = array(
            'user1' => 'key1',
            'user2' => 'key2',
            'user3' => [
                'ro' => 'rokey',
                'rw' => ['rwkey1', 'rwkey2']
            ]
        );

        return array(
            'no users exists' => array(array(), 'public', null),
            'user exists' => array($users, 'user2', ['key2']),
            'user does not exist' => array($users, 'user4', null),
            'user has ro and rw keys' => array($users, 'user3', ['rokey', 'rwkey1', 'rwkey2'])
        );
    }

    /**
     * @dataProvider getUsers
     */
    public function testCanLookupAUser(array $users, $publicKey, $privateKeys) {
        $storage = new ArrayStorage($users);
        $this->assertSame($privateKeys, $storage->getPrivateKeys($publicKey));
    }

    public function testCanGetReadOnlyPrivateKeys() {
        $storage = new ArrayStorage([
            'user'  => ['ro' => 'read-only'],
            'user2' => ['ro' => ['ro1', 'ro2']]
        ]);

        $mode = UserLookupInterface::MODE_READ_ONLY;
        $this->assertSame(['read-only'], $storage->getPrivateKeys('user', $mode));
        $this->assertSame(['ro1', 'ro2'], $storage->getPrivateKeys('user2', $mode));
    }

    public function testCanGetReadWritePrivateKeys() {
        $storage = new ArrayStorage([
            'user'  => ['rw' => 'read+write'],
            'user2' => ['rw' => ['rw1', 'rw2']],
        ]);

        $mode = UserLookupInterface::MODE_READ_WRITE;
        $this->assertSame(['read+write'], $storage->getPrivateKeys('user', $mode));
        $this->assertSame(['rw1', 'rw2'], $storage->getPrivateKeys('user2', $mode));
        $this->assertSame(null, $storage->getPrivateKeys('user', UserLookupInterface::MODE_READ_ONLY));
    }

    public function testCanGetAllPrivateKeysForUser() {
        $storage = new ArrayStorage([
            'user'  => [
                'rw' => 'read+write',
                'ro' => ['ro1', 'ro2']
            ]
        ]);

        $this->assertSame(['ro1', 'ro2', 'read+write'], $storage->getPrivateKeys('user'));
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getUsersAndQuery() {
        $users = array(
            'user1' => 'key1',
            'user2' => 'key2',
            'user3' => 'key3',
            'user4' => 'key4',
            'user5' => 'key5',
            'user6' => 'key6',
        );

        return array(
            'empty query' => array(
                $users,
                new Query(),
                array_keys($users),
            ),
            'query with limit and offset' => array(
                $users,
                (new Query())->limit(2)->offset(3),
                array(
                    'user4', 'user5',
                ),
            ),
            'query with limit out of bounds' => array(
                $users,
                (new Query())->limit(4)->offset(5),
                array(
                    'user6',
                ),
            ),
            'query with offset out of bounds' => array(
                $users,
                (new Query())->limit(4)->offset(10),
                array(),
            ),
        );
    }

    /**
     * @dataProvider getUsersAndQuery
     */
    public function testCanGetUsers(array $users, Query $query, array $expectedUsers = array()) {
        $storage = new ArrayStorage($users);
        $this->assertSame($expectedUsers, $storage->getUsers($query));
    }

    public function testPublicKeyExists() {
        $storage = new ArrayStorage([
            'user'  => 'key',
            'user2' => ['ro' => 'key', 'rw' => 'key2']
        ]);

        $this->assertTrue($storage->publicKeyExists('user'));
        $this->assertTrue($storage->publicKeyExists('user2'));
        $this->assertFalse($storage->publicKeyExists('user3'));
    }
}
