<?php declare(strict_types=1);
namespace Imbo\Model;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\AccessRule
 */
class AccessRuleTest extends TestCase
{
    private AccessRule $model;

    public function setUp(): void
    {
        $this->model = new AccessRule();
    }

    /**
     * @covers ::getId
     * @covers ::setId
     */
    public function testSetAndGetId(): void
    {
        $this->assertNull($this->model->getId());
        $this->assertSame($this->model, $this->model->setId(1));
        $this->assertSame(1, $this->model->getId());
    }

    /**
     * @covers ::getGroup
     * @covers ::setGroup
     */
    public function testSetAndGetGroup(): void
    {
        $this->assertNull($this->model->getGroup());
        $this->assertSame($this->model, $this->model->setGroup('name'));
        $this->assertSame('name', $this->model->getGroup());
    }

    /**
     * @covers ::getResources
     * @covers ::setResources
     */
    public function testSetAndGetResources(): void
    {
        $this->assertSame([], $this->model->getResources());
        $this->assertSame($this->model, $this->model->setResources(['r1', 'r2']));
        $this->assertSame(['r1', 'r2'], $this->model->getResources());
    }

    /**
     * @covers ::getUsers
     * @covers ::setUsers
     */
    public function testSetAndGetUsers(): void
    {
        $this->assertSame([], $this->model->getUsers());
        $this->assertSame($this->model, $this->model->setUsers(['u1', 'u2']));
        $this->assertSame(['u1', 'u2'], $this->model->getUsers());
    }

    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
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
