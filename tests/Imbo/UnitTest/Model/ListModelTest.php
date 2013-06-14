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

use Imbo\Model\ListModel;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
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
        $this->assertSame(array(), $this->model->getList());
        $list = array(1, 2, 3);
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
}
