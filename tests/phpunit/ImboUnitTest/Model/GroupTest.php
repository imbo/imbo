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

use Imbo\Model\Group;

/**
 * @covers Imbo\Model\Group
 * @group unit
 * @group models
 */
class GroupTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Group
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new Group();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\Group::getName
     * @covers Imbo\Model\Group::setName
     */
    public function testSetAndGetName() {
        $this->assertNull($this->model->getName());
        $this->assertSame($this->model, $this->model->setName('name'));
        $this->assertSame('name', $this->model->getName());
    }

    /**
     * @covers Imbo\Model\Group::getResources
     * @covers Imbo\Model\Group::setResources
     */
    public function testSetAndGetResources() {
        $this->assertSame([], $this->model->getResources());
        $this->assertSame($this->model, $this->model->setResources(['image.get', 'image.head']));
        $this->assertSame(['image.get', 'image.head'], $this->model->getResources());
    }

    /**
     * @covers Imbo\Model\Group::getData
     */
    public function testGetData() {
        $this->model
            ->setName('name')
            ->setResources(['image.get', 'image.head']);

        $this->assertSame([
            'name' => 'name',
            'resources' => ['image.get', 'image.head'],
        ], $this->model->getData());
    }
}
