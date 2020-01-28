<?php declare(strict_types=1);
namespace Imbo\Model;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\ListModel
 */
class ListModelTest extends TestCase {
    private $model;

    public function setUp() : void {
        $this->model = new ListModel();
    }

    /**
     * @covers ::setList
     * @covers ::getList
     */
    public function testCanSetAndGetAList() : void {
        $this->assertSame([], $this->model->getList());
        $list = [1, 2, 3];
        $this->assertSame($this->model, $this->model->setList($list));
        $this->assertSame($list, $this->model->getList());
    }

    /**
     * @covers ::setContainer
     * @covers ::getContainer
     */
    public function testCanSetAndGetTheContainerValue() : void {
        $this->assertNull($this->model->getContainer());
        $container = 'container';
        $this->assertSame($this->model, $this->model->setContainer($container));
        $this->assertSame($container, $this->model->getContainer());
    }

    /**
     * @covers ::__construct
     * @covers ::getData
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
