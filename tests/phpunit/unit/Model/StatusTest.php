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

use Imbo\Model\Status,
    DateTime;

/**
 * @covers Imbo\Model\Status
 * @group unit
 * @group models
 */
class StatusTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Status
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new Status();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\Status::getDate
     * @covers Imbo\Model\Status::setDate
     */
    public function testCanSetAndGetDate() {
        $date = new DateTime();
        $this->assertNull($this->model->getDate());
        $this->assertSame($this->model, $this->model->setDate($date));
        $this->assertSame($date, $this->model->getDate());
    }

    /**
     * @covers Imbo\Model\Status::getDatabaseStatus
     * @covers Imbo\Model\Status::setDatabaseStatus
     */
    public function testCanSetAndGetDatabaseStatus() {
        $this->assertNull($this->model->getDatabaseStatus());
        $this->assertSame($this->model, $this->model->setDatabaseStatus(true));
        $this->assertTrue($this->model->getDatabaseStatus());
    }

    /**
     * @covers Imbo\Model\Status::getStorageStatus
     * @covers Imbo\Model\Status::setStorageStatus
     */
    public function testCanSetAndGetStorageStatus() {
        $this->assertNull($this->model->getStorageStatus());
        $this->assertSame($this->model, $this->model->setStorageStatus(true));
        $this->assertTrue($this->model->getStorageStatus());
    }

    /**
     * @covers Imbo\Model\Status::getData
     */
    public function testGetData() {
        $date = new DateTime();

        $this->model
            ->setDate($date)
            ->setDatabaseStatus(true)
            ->setStorageStatus(true);

        $this->assertSame([
            'date' => $date,
            'database' => true,
            'storage' => true,
        ], $this->model->getData());
    }
}
