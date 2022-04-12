<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Auth\AccessControl\GroupQuery
 */
class GroupQueryTest extends TestCase
{
    private $query;

    public function setUp(): void
    {
        $this->query = new GroupQuery();
    }

    /**
     * @covers ::setLimit
     * @covers ::getLimit
     */
    public function testSetAndGetLimit(): void
    {
        $this->assertSame(20, $this->query->getLimit());
        $this->assertSame($this->query, $this->query->setLimit(10));
        $this->assertSame(10, $this->query->getLimit());
    }

    /**
     * @covers ::setPage
     * @covers ::getPage
     */
    public function testSetAndGetPage(): void
    {
        $this->assertSame(1, $this->query->getPage());
        $this->assertSame($this->query, $this->query->setPage(2));
        $this->assertSame(2, $this->query->getPage());
    }
}
