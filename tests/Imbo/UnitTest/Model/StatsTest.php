<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Model;

use Imbo\Model\Stats;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class StatsTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Stats
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new Stats();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * Get users
     *
     * @return array[]
     */
    public function getUsers() {
        return array(
            array(
                array(),
                0,
                0,
                0
            ),
            array(
                array(
                    'user' => array('numImages' => 2, 'numBytes' => 1349),
                ),
                1,
                2,
                1349
            ),
            array(
                array(
                    'user1' => array('numImages' => 2, 'numBytes' => 1349),
                    'user2' => array('numImages' => 42, 'numBytes' => 98765),
                ),
                2,
                44,
                100114
            ),
            array(
                array(
                    'user1' => array('numImages' => 2, 'numBytes' => 100),
                    'user2' => array('numImages' => 3, 'numBytes' => 200),
                    'user3' => array('numImages' => 4, 'numBytes' => 300),
                    'user4' => array('numImages' => 5, 'numBytes' => 400),
                ),
                4,
                14,
                1000
            ),
        );
    }

    /**
     * @dataProvider getUsers
     * @covers Imbo\Model\Stats::getUsers
     * @covers Imbo\Model\Stats::setUsers
     */
    public function testCanSetAndGetUsers(array $users, $numUsers, $images, $bytes) {
        $this->assertSame(array(), $this->model->getUsers());
        $this->assertSame($this->model, $this->model->setUsers($users));
        $this->assertSame($users, $this->model->getUsers());
    }

    /**
     * @dataProvider getUsers
     * @covers Imbo\Model\Stats::getNumUsers
     */
    public function testCanGetNumberOfUsers(array $users, $numUsers, $images, $bytes) {
        $this->assertSame(0, $this->model->getNumUsers());
        $this->model->setUsers($users);
        $this->assertSame($numUsers, $this->model->getNumUsers());
    }

    /**
     * @dataProvider getUsers
     * @covers Imbo\Model\Stats::getNumImages
     */
    public function testCanGetTotalAmountOfImages(array $users, $numUsers, $images, $bytes) {
        $this->model->setUsers($users);
        $this->assertSame($images, $this->model->getNumImages());
    }

    /**
     * @dataProvider getUsers
     * @covers Imbo\Model\Stats::getNumBytes
     */
    public function testCanGetTotalAmountOfBytes(array $users, $numUsers, $images, $bytes) {
        $this->model->setUsers($users);
        $this->assertSame($bytes, $this->model->getNumBytes());
    }
}
