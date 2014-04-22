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
        );

        return array(
            'no users exists' => array(array(), 'public', null),
            'user exists' => array($users, 'user2', 'key2'),
            'user does not exist' => array($users, 'user3', null),
        );
    }

    /**
     * @dataProvider getUsers
     */
    public function testCanLookupAUser(array $users, $publicKey, $privateKey) {
        $storage = new ArrayStorage($users);
        $this->assertSame($privateKey, $storage->getPrivateKey($publicKey));
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
                array('user4', 'user5'),
            ),
            'query with limit out of bounds' => array(
                $users,
                (new Query())->limit(4)->offset(5),
                array('user6'),
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
    public function testCanGetPublicKeys(array $users, Query $query, array $expectedUsers = array()) {
        $storage = new ArrayStorage($users);
        $this->assertSame($expectedUsers, $storage->getPublicKeys($query));
    }
}
