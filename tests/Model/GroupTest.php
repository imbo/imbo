<?php declare(strict_types=1);
namespace Imbo\Model;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\Group
 */
class GroupTest extends TestCase
{
    private Group $model;

    public function setUp(): void
    {
        $this->model = new Group();
    }

    /**
     * @covers ::getName
     * @covers ::setName
     */
    public function testSetAndGetName(): void
    {
        $this->assertNull($this->model->getName());
        $this->assertSame($this->model, $this->model->setName('name'));
        $this->assertSame('name', $this->model->getName());
    }

    /**
     * @covers ::getResources
     * @covers ::setResources
     */
    public function testSetAndGetResources(): void
    {
        $this->assertSame([], $this->model->getResources());
        $this->assertSame($this->model, $this->model->setResources(['image.get', 'image.head']));
        $this->assertSame(['image.get', 'image.head'], $this->model->getResources());
    }

    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $this->model
            ->setName('name')
            ->setResources(['image.get', 'image.head']);

        $this->assertSame([
            'name' => 'name',
            'resources' => ['image.get', 'image.head'],
        ], $this->model->getData());
    }
}
