<?php declare(strict_types=1);
namespace Imbo\Resource\Images;

use Imbo\Exception\RuntimeException;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Resource\Images\Query
 */
class QueryTest extends TestCase
{
    private Query $query;

    public function setUp(): void
    {
        $this->query = new Query();
    }

    /**
     * @covers ::setPage
     * @covers ::getPage
     */
    public function testPage(): void
    {
        $value = 2;
        $this->assertSame(1, $this->query->getPage());
        $this->assertSame($this->query, $this->query->setPage($value));
        $this->assertSame($value, $this->query->getPage());
    }

    /**
     * @covers ::setLimit
     * @covers ::getLimit
     */
    public function testLimit(): void
    {
        $value = 30;
        $this->assertSame(20, $this->query->getLimit());
        $this->assertSame($this->query, $this->query->setLimit($value));
        $this->assertSame($value, $this->query->getLimit());
    }

    /**
     * @covers ::setReturnMetadata
     * @covers ::getReturnMetadata
     */
    public function testReturnMetadata(): void
    {
        $this->assertFalse($this->query->getReturnMetadata());
        $this->assertSame($this->query, $this->query->setReturnMetadata(true));
        $this->assertTrue($this->query->getReturnMetadata());
    }

    /**
     * @covers ::setFrom
     * @covers ::getFrom
     */
    public function testFrom(): void
    {
        $value = 123123123;
        $this->assertNull($this->query->getFrom());
        $this->assertSame($this->query, $this->query->setFrom($value));
        $this->assertSame($value, $this->query->getFrom());
    }

    /**
     * @covers ::setTo
     * @covers ::getTo
     */
    public function testTo(): void
    {
        $value = 123123123;
        $this->assertNull($this->query->getTo());
        $this->assertSame($this->query, $this->query->setTo($value));
        $this->assertSame($value, $this->query->getTo());
    }

    /**
     * @covers ::setImageIdentifiers
     * @covers ::getImageIdentifiers
     */
    public function testImageIdentifiers(): void
    {
        $value = ['id1', 'id2'];
        $this->assertSame([], $this->query->getImageIdentifiers());
        $this->assertSame($this->query, $this->query->setImageIdentifiers($value));
        $this->assertSame($value, $this->query->getImageIdentifiers());
    }

    /**
     * @covers ::setChecksums
     * @covers ::getChecksums
     */
    public function testChecksums(): void
    {
        $value = ['sum1', 'sum2'];
        $this->assertSame([], $this->query->getChecksums());
        $this->assertSame($this->query, $this->query->setChecksums($value));
        $this->assertSame($value, $this->query->getChecksums());
    }

    /**
     * @covers ::setOriginalChecksums
     * @covers ::getOriginalChecksums
     */
    public function testOriginalChecksums(): void
    {
        $value = ['sum1', 'sum2'];
        $this->assertSame([], $this->query->getOriginalChecksums());
        $this->assertSame($this->query, $this->query->setOriginalChecksums($value));
        $this->assertSame($value, $this->query->getOriginalChecksums());
    }

    public function getSortData(): array
    {
        return [
            'single field without sort' => [
                ['field1'],
                [
                    [
                        'field' => 'field1',
                        'sort' => 'asc',
                    ],
                ],
            ],
            'single field with sort' => [
                ['field1:desc'],
                [
                    [
                        'field' => 'field1',
                        'sort' => 'desc',
                    ],
                ],
            ],
            'multiple fields' => [
                ['field1', 'field2:desc', 'field3:asc'],
                [
                    [
                        'field' => 'field1',
                        'sort' => 'asc',
                    ],
                    [
                        'field' => 'field2',
                        'sort' => 'desc',
                    ],
                    [
                        'field' => 'field3',
                        'sort' => 'asc',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getSortData
     * @covers ::setSort
     * @covers ::getSort
     */
    public function testSort(array $value, array $formatted): void
    {
        $this->assertSame([], $this->query->getSort());
        $this->assertSame($this->query, $this->query->setSort($value));
        $this->assertSame($formatted, $this->query->getSort());
    }

    /**
     * @covers ::setSort
     */
    public function testSortThrowsExceptionOnInvalidSortValues(): void
    {
        $this->expectExceptionObject(new RuntimeException('Invalid sort value: field:foo', Response::HTTP_BAD_REQUEST));
        $this->query->setSort(['field:foo']);
    }

    /**
     * @covers ::setSort
     */
    public function testSortThrowsExceptionWhenTheStortStringIsBadlyFormatted(): void
    {
        $this->expectExceptionObject(new RuntimeException('Badly formatted sort', Response::HTTP_BAD_REQUEST));
        $this->query->setSort(['field:asc', '']);
    }
}
