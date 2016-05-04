<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Model;

use Imbo\Model\User,
    DateTime;

/**
 * @covers Imbo\Model\User
 * @group unit
 * @group models
 */
class UserTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var User
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new User();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\User::getUserId
     * @covers Imbo\Model\User::setUserId
     */
    public function testCanSetAndGetUserId() {
        $this->assertNull($this->model->getUserId());
        $this->assertSame($this->model, $this->model->setUserId('key'));
        $this->assertSame('key', $this->model->getUserId());
    }

    /**
     * @covers Imbo\Model\User::getNumImages
     * @covers Imbo\Model\User::setNumImages
     */
    public function testCanSetAndGetNumImages() {
        $this->assertNull($this->model->getNumImages());
        $this->assertSame($this->model, $this->model->setNumImages(10));
        $this->assertSame(10, $this->model->getNumImages());
    }

    /**
     * @covers Imbo\Model\User::getLastModified
     * @covers Imbo\Model\User::setLastModified
     */
    public function testCanSetAndGetLastModified() {
        $date = new DateTime();
        $this->assertNull($this->model->getLastModified());
        $this->assertSame($this->model, $this->model->setLastModified($date));
        $this->assertSame($date, $this->model->getLastModified());
    }

    /**
     * @covers Imbo\Model\User::getData
     */
    public function testGetData() {
        $date = new DateTime();
        $this->model
            ->setUserId('id')
            ->setNumImages(100)
            ->setLastModified($date);

        $this->assertSame([
            'id' => 'id',
            'numImages' => 100,
            'lastModified' => $date,
        ], $this->model->getData());
    }
}
