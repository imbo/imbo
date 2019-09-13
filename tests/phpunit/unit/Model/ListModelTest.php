<?php
namespace ImboUnitTest\Model;

use Imbo\Model\ListModel;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\ListModel
 */
class ListModelTest extends TestCase {
    /**
     * @var ListModel
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() : void {
        $this->model = new ListModel();
    }

    /**
     * @covers Imbo\Model\ListModel::setList
     * @covers Imbo\Model\ListModel::getList
     */
    public function testCanSetAndGetAList() : void {
        $this->assertSame([], $this->model->getList());
        $list = [1, 2, 3];
        $this->assertSame($this->model, $this->model->setList($list));
        $this->assertSame($list, $this->model->getList());
    }

    /**
     * @covers Imbo\Model\ListModel::setContainer
     * @covers Imbo\Model\ListModel::getContainer
     */
    public function testCanSetAndGetTheContainerValue() : void {
        $this->assertNull($this->model->getContainer());
        $container = 'container';
        $this->assertSame($this->model, $this->model->setContainer($container));
        $this->assertSame($container, $this->model->getContainer());
    }

    /**
     * @covers Imbo\Model\ListModel::__construct
     * @covers Imbo\Model\ListModel::getData
     */
    public function testGetData() : void {
        $container = 'container';
        $list = [1, 2, 3];

        $model = new ListModel($container, $list);

        $this->assertSame([
            'container' => $container,
            'list' => $list,
        ], $model->getData());
    }
}
