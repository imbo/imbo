<?php
namespace ImboUnitTest\Resource\Images;

use Imbo\Resource\Images\Query;
use Imbo\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Resource\Images\Query
 */
class QueryTest extends TestCase {
    /**
     * @var Query
     */
    private $query;

    /**
     * Set up the query
     */
    public function setUp() : void {
        $this->query = new Query();
    }

    /**
     * @covers Imbo\Resource\Images\Query::page
     */
    public function testPage() : void {
        $value = 2;
        $this->assertSame(1, $this->query->page());
        $this->assertSame($this->query, $this->query->page($value));
        $this->assertSame($value, $this->query->page());
    }

    /**
     * @covers Imbo\Resource\Images\Query::limit
     */
    public function testLimit() : void {
        $value = 30;
        $this->assertSame(20, $this->query->limit());
        $this->assertSame($this->query, $this->query->limit($value));
        $this->assertSame($value, $this->query->limit());
    }

    /**
     * @covers Imbo\Resource\Images\Query::returnMetadata
     */
    public function testReturnMetadata() : void {
        $this->assertFalse($this->query->returnMetadata());
        $this->assertSame($this->query, $this->query->returnMetadata(true));
        $this->assertTrue($this->query->returnMetadata());
    }

    /**
     * @covers Imbo\Resource\Images\Query::from
     */
    public function testFrom() : void {
        $value = 123123123;
        $this->assertNull($this->query->from());
        $this->assertSame($this->query, $this->query->from($value));
        $this->assertSame($value, $this->query->from());
    }

    /**
     * @covers Imbo\Resource\Images\Query::to
     */
    public function testTo() : void {
        $value = 123123123;
        $this->assertNull($this->query->to());
        $this->assertSame($this->query, $this->query->to($value));
        $this->assertSame($value, $this->query->to());
    }

    /**
     * @covers Imbo\Resource\Images\Query::imageIdentifiers
     */
    public function testImageIdentifiers() : void {
        $value = ['id1', 'id2'];
        $this->assertSame([], $this->query->imageIdentifiers());
        $this->assertSame($this->query, $this->query->imageIdentifiers($value));
        $this->assertSame($value, $this->query->imageIdentifiers());
    }

    /**
     * @covers Imbo\Resource\Images\Query::checksums
     */
    public function testChecksums() : void {
        $value = ['sum1', 'sum2'];
        $this->assertSame([], $this->query->checksums());
        $this->assertSame($this->query, $this->query->checksums($value));
        $this->assertSame($value, $this->query->checksums());
    }

    /**
     * @covers Imbo\Resource\Images\Query::originalChecksums
     */
    public function testOriginalChecksums() : void {
        $value = ['sum1', 'sum2'];
        $this->assertSame([], $this->query->originalChecksums());
        $this->assertSame($this->query, $this->query->originalChecksums($value));
        $this->assertSame($value, $this->query->originalChecksums());
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getSortData() {
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
     * @covers Imbo\Resource\Images\Query::sort
     */
    public function testSort(array $value, $formatted) : void {
        $this->assertSame([], $this->query->sort());
        $this->assertSame($this->query, $this->query->sort($value));
        $this->assertSame($formatted, $this->query->sort());
    }

    /**
     * @covers Imbo\Resource\Images\Query::sort
     */
    public function testSortThrowsExceptionOnInvalidSortValues() : void {
        $this->expectExceptionObject(new RuntimeException('Invalid sort value: field:foo', 400));
        $this->query->sort(['field:foo']);
    }

    public function testSortThrowsExceptionWhenTheStortStringIsBadlyFormatted() : void {
        $this->expectExceptionObject(new RuntimeException('Badly formatted sort', 400));
        $this->query->sort(['field:asc', '']);
    }
}
