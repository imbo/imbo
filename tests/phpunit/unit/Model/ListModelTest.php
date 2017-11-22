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
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Model\ListModel
 * @group unit
 * @group models
 */
class ListModelTest extends TestCase {
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
     * @covers Imbo\Model\ListModel::__construct
     * @covers Imbo\Model\ListModel::getData
     */
    public function testGetData() {
        $container = 'container';
        $list = [1, 2, 3];

        $model = new ListModel($container, $list);

        $this->assertSame([
            'container' => $container,
            'list' => $list,
        ], $model->getData());
    }
}
