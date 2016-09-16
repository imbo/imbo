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

use Imbo\Model\Groups;

/**
 * @covers Imbo\Model\Groups
 * @group unit
 * @group models
 */
class GroupsTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Groups
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new Groups();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\Groups::getGroups
     * @covers Imbo\Model\Groups::setGroups
     */
    public function testSetAndGetGroups() {
        $this->assertSame([], $this->model->getGroups());
        $this->assertSame($this->model, $this->model->setGroups(['group' => [], 'group2' => []]));
        $this->assertSame(['group' => [], 'group2' => []], $this->model->getGroups());
    }

    /**
     * @covers Imbo\Model\Groups::setHits
     * @covers Imbo\Model\Groups::getHits
     */
    public function testCanSetAndGetHits() {
        $this->assertNull($this->model->getHits());
        $this->assertSame($this->model, $this->model->setHits(10));
        $this->assertSame(10, $this->model->getHits());
    }

    /**
     * @covers Imbo\Model\Groups::setPage
     * @covers Imbo\Model\Groups::getPage
     */
    public function testCanSetAndGetPage() {
        $this->assertNull($this->model->getPage());
        $this->assertSame($this->model, $this->model->setPage(10));
        $this->assertSame(10, $this->model->getPage());
    }

    /**
     * @covers Imbo\Model\Groups::setLimit
     * @covers Imbo\Model\Groups::getLimit
     */
    public function testCanSetAndGetLimit() {
        $this->assertNull($this->model->getLimit());
        $this->assertSame($this->model, $this->model->setLimit(10));
        $this->assertSame(10, $this->model->getLimit());
    }

    /**
     * @covers Imbo\Model\Groups::getCount
     */
    public function testCanCountImages() {
        $this->assertSame(0, $this->model->getCount());
        $this->assertSame($this->model, $this->model->setGroups(['group1' => [], 'group2' => []]));
        $this->assertSame(2, $this->model->getCount());
    }

    /**
     * @covers Imbo\Model\Groups::getData
     */
    public function testGetData() {
        $this->model
            ->setGroups(['group' => [], 'group2' => []])
            ->setHits(10)
            ->setPage(10)
            ->setLimit(10);

        $this->assertSame([
            'groups' => ['group' => [], 'group2' => []],
            'count' => 2,
            'hits' => 10,
            'limit' => 10,
            'page' => 10,
        ], $this->model->getData());
    }
}
