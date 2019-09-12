<?php
namespace ImboUnitTest\Model;

use Imbo\Model\AccessRule;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\AccessRule
 */
class AccessRuleTest extends TestCase {
    /**
     * @var AccessRule
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() : void {
        $this->model = new AccessRule();
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
