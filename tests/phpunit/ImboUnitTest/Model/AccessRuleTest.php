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

use Imbo\Model\AccessRule;

/**
 * @covers Imbo\Model\AccessRule
 * @group unit
 * @group models
 */
class AccessRuleTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var AccessRule
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new AccessRule();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\AccessRule::getId
     * @covers Imbo\Model\AccessRule::setId
     */
    public function testSetAndGetId() {
        $this->assertNull($this->model->getId());
        $this->assertSame($this->model, $this->model->setId(1));
        $this->assertSame(1, $this->model->getId());
    }

    /**
     * @covers Imbo\Model\AccessRule::getGroup
     * @covers Imbo\Model\AccessRule::setGroup
     */
    public function testSetAndGetGroup() {
        $this->assertNull($this->model->getGroup());
        $this->assertSame($this->model, $this->model->setGroup('name'));
        $this->assertSame('name', $this->model->getGroup());
    }

    /**
     * @covers Imbo\Model\AccessRule::getResources
     * @covers Imbo\Model\AccessRule::setResources
     */
    public function testSetAndGetResources() {
        $this->assertSame([], $this->model->getResources());
        $this->assertSame($this->model, $this->model->setResources(['r1', 'r2']));
        $this->assertSame(['r1', 'r2'], $this->model->getResources());
    }

    /**
     * @covers Imbo\Model\AccessRule::getUsers
     * @covers Imbo\Model\AccessRule::setUsers
     */
    public function testSetAndGetUsers() {
        $this->assertSame([], $this->model->getUsers());
        $this->assertSame($this->model, $this->model->setUsers(['u1', 'u2']));
        $this->assertSame(['u1', 'u2'], $this->model->getUsers());
    }

    /**
     * @covers Imbo\Model\AccessRule::getData
     */
    public function testGetData() {
        $this->model
            ->setId(1)
            ->setGroup('name')
            ->setResources(['r1', 'r2'])
            ->setUsers(['u1', 'u2']);

        $this->assertSame([
            'id' => 1,
            'group' => 'name',
            'resources' => ['r1', 'r2'],
            'users' => ['u1', 'u2'],
        ], $this->model->getData());
    }
}
