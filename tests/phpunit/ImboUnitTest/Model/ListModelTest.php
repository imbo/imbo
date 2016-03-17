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

use Imbo\Model\ListModel;

/**
 * @covers Imbo\Model\ListModel
 * @group unit
 * @group models
 */
class ListModelTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ListModel
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new ListModel();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\ListModel::setList
     * @covers Imbo\Model\ListModel::getList
     */
    public function testCanSetAndGetAList() {
        $this->assertSame([], $this->model->getList());
        $list = [1, 2, 3];
        $this->assertSame($this->model, $this->model->setList($list));
        $this->assertSame($list, $this->model->getList());
    }

    /**
     * @covers Imbo\Model\ListModel::setContainer
     * @covers Imbo\Model\ListModel::getContainer
     */
    public function testCanSetAndGetTheContainerValue() {
        $this->assertNull($this->model->getContainer());
        $container = 'container';
        $this->assertSame($this->model, $this->model->setContainer($container));
        $this->assertSame($container, $this->model->getContainer());
    }

    /**
     * @covers Imbo\Model\ListModel::setEntry
     * @covers Imbo\Model\ListModel::getEntry
     */
    public function testCanSetAndGetAnEntryValue() {
        $this->assertNull($this->model->getEntry());
        $entry = 'entry';
        $this->assertSame($this->model, $this->model->setEntry($entry));
        $this->assertSame($entry, $this->model->getEntry());
    }

    /**
     * @covers Imbo\Model\ListModel::getData
     */
    public function testGetData() {
        $list = [1, 2, 3];
        $container = 'container';
        $entry = 'entry';

        $this->model
            ->setList($list)
            ->setContainer($container)
            ->setEntry($entry);

        $this->assertSame([
            'list' => $list,
            'container' => $container,
            'entry' => $entry,
        ], $this->model->getData());
    }
}
