<?php declare(strict_types=1);

namespace Imbo\Auth\AccessControl;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroupQuery::class)]
class GroupQueryTest extends TestCase
{
    private GroupQuery $query;

    protected function setUp(): void
    {
        $this->query = new GroupQuery();
    }

    public function testSetAndGetLimit(): void
    {
        $this->assertSame(20, $this->query->getLimit());
        $this->assertSame($this->query, $this->query->setLimit(10));
        $this->assertSame(10, $this->query->getLimit());
    }

    public function testSetAndGetPage(): void
    {
        $this->assertSame(1, $this->query->getPage());
        $this->assertSame($this->query, $this->query->setPage(2));
        $this->assertSame(2, $this->query->getPage());
    }
}
