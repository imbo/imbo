<?php
namespace ImboUnitTest\Model;

use Imbo\Model\ArrayModel;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\ArrayModel
 */
class ArrayModelTest extends TestCase {
    /**
     * @var ArrayModel
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() : void {
        $this->model = new ArrayModel();
    }

    /**
     * @covers Imbo\Model\ArrayModel::getData
     * @covers Imbo\Model\ArrayModel::setData
     */
    public function testCanSetAndGetData() {
        $this->assertSame([], $this->model->getData());
        $this->assertSame($this->model, $this->model->setData(['key' => 'value']));
        $this->assertSame(['key' => 'value'], $this->model->getData());
    }

    /**
     * @covers Imbo\Model\ArrayModel::setTitle
     * @covers Imbo\Model\ArrayModel::getTitle
     */
    public function testCanSetAndGetTitle() {
        $this->assertNull($this->model->getTitle());
        $this->assertSame($this->model, $this->model->setTitle('title'));
        $this->assertSame('title', $this->model->getTitle());
    }
}
