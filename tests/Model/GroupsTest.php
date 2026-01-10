<?php declare(strict_types=1);

namespace Imbo\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Groups::class)]
class GroupsTest extends TestCase
{
    private Groups $model;

    protected function setUp(): void
    {
        $this->model = new Groups();
    }

    public function testSetAndGetGroups(): void
    {
        $this->assertSame([], $this->model->getGroups());
        $this->assertSame($this->model, $this->model->setGroups(['group' => [], 'group2' => []]));
        $this->assertSame(['group' => [], 'group2' => []], $this->model->getGroups());
    }

    public function testCanSetAndGetHits(): void
    {
        $this->assertNull($this->model->getHits());
        $this->assertSame($this->model, $this->model->setHits(10));
        $this->assertSame(10, $this->model->getHits());
    }

    public function testCanSetAndGetPage(): void
    {
        $this->assertNull($this->model->getPage());
        $this->assertSame($this->model, $this->model->setPage(10));
        $this->assertSame(10, $this->model->getPage());
    }

    public function testCanSetAndGetLimit(): void
    {
        $this->assertNull($this->model->getLimit());
        $this->assertSame($this->model, $this->model->setLimit(10));
        $this->assertSame(10, $this->model->getLimit());
    }

    public function testCanCountImages(): void
    {
        $this->assertSame(0, $this->model->getCount());
        $this->assertSame($this->model, $this->model->setGroups(['group1' => [], 'group2' => []]));
        $this->assertSame(2, $this->model->getCount());
    }

    public function testGetData(): void
    {
        $this->model
            ->setGroups(['group' => [], 'group2' => []])
            ->setHits(10)
            ->setPage(10)
            ->setLimit(10);

        $this->assertSame([
            'groups' => ['group' => [], 'group2' => []],
            'count' => 2,
            'hits' => 10,
            'limit' => 10,
            'page' => 10,
        ], $this->model->getData());
    }
}
