<?php declare(strict_types=1);

namespace Imbo\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Group::class)]
class GroupTest extends TestCase
{
    private Group $model;

    protected function setUp(): void
    {
        $this->model = new Group();
    }

    public function testSetAndGetName(): void
    {
        $this->assertNull($this->model->getName());
        $this->assertSame($this->model, $this->model->setName('name'));
        $this->assertSame('name', $this->model->getName());
    }

    public function testSetAndGetResources(): void
    {
        $this->assertSame([], $this->model->getResources());
        $this->assertSame($this->model, $this->model->setResources(['image.get', 'image.head']));
        $this->assertSame(['image.get', 'image.head'], $this->model->getResources());
    }

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
