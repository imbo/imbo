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

use Imbo\Model\ArrayModel;

/**
 * @covers Imbo\Model\ArrayModel
 * @group unit
 * @group models
 */
class ArrayModelTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ArrayModel
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new ArrayModel();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
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
