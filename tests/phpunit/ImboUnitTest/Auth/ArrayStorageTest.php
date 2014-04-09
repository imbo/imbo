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

use Imbo\Auth\ArrayStorage;

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

    public function testCanIterateOverUsers() {
        $users = array(
            'user1' => 'key1',
            'user2' => 'key2',
            'user3' => 'key3',
        );

        $storage = new ArrayStorage($users);
        $keys = array();
        $both = array();

        foreach ($storage as $value) {
            $keys[] = $value;
        }

        $this->assertSame($keys, array('key1', 'key2', 'key3'));

        foreach ($storage as $key => $value) {
            $both[$key] = $value;
        }

        $this->assertSame($both, $users);
    }
}
