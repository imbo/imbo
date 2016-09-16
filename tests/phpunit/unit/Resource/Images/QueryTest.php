<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Resource\Images;

use Imbo\Resource\Images\Query;

/**
 * @covers Imbo\Resource\Images\Query
 * @group unit
 * @group resources
 */
class QueryTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Query
     */
    private $query;

    /**
     * Set up the query
     */
    public function setUp() {
        $this->query = new Query();
    }

    /**
     * Tear down the query
     */
    public function tearDown() {
        $this->query = null;
    }

    /**
     * @covers Imbo\Resource\Images\Query::page
     */
    public function testPage() {
        $value = 2;
        $this->assertSame(1, $this->query->page());
        $this->assertSame($this->query, $this->query->page($value));
        $this->assertSame($value, $this->query->page());
    }

    /**
     * @covers Imbo\Resource\Images\Query::limit
     */
    public function testLimit() {
        $value = 30;
        $this->assertSame(20, $this->query->limit());
        $this->assertSame($this->query, $this->query->limit($value));
        $this->assertSame($value, $this->query->limit());
    }

    /**
     * @covers Imbo\Resource\Images\Query::returnMetadata
     */
    public function testReturnMetadata() {
        $this->assertFalse($this->query->returnMetadata());
        $this->assertSame($this->query, $this->query->returnMetadata(true));
        $this->assertTrue($this->query->returnMetadata());
    }

    /**
     * @covers Imbo\Resource\Images\Query::from
     */
    public function testFrom() {
        $value = 123123123;
        $this->assertNull($this->query->from());
        $this->assertSame($this->query, $this->query->from($value));
        $this->assertSame($value, $this->query->from());
    }

    /**
     * @covers Imbo\Resource\Images\Query::to
     */
    public function testTo() {
        $value = 123123123;
        $this->assertNull($this->query->to());
        $this->assertSame($this->query, $this->query->to($value));
        $this->assertSame($value, $this->query->to());
    }

    /**
     * @covers Imbo\Resource\Images\Query::imageIdentifiers
     */
    public function testImageIdentifiers() {
        $value = ['id1', 'id2'];
        $this->assertSame([], $this->query->imageIdentifiers());
        $this->assertSame($this->query, $this->query->imageIdentifiers($value));
        $this->assertSame($value, $this->query->imageIdentifiers());
    }

    /**
     * @covers Imbo\Resource\Images\Query::checksums
     */
    public function testChecksums() {
        $value = ['sum1', 'sum2'];
        $this->assertSame([], $this->query->checksums());
        $this->assertSame($this->query, $this->query->checksums($value));
        $this->assertSame($value, $this->query->checksums());
    }

    /**
     * @covers Imbo\Resource\Images\Query::originalChecksums
     */
    public function testOriginalChecksums() {
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
    public function testSort(array $value, $formatted) {
        $this->assertSame([], $this->query->sort());
        $this->assertSame($this->query, $this->query->sort($value));
        $this->assertSame($formatted, $this->query->sort());
    }

    /**
     * @covers Imbo\Resource\Images\Query::sort
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Invalid sort value: field:foo
     * @expectedExceptionCode 400
     */
    public function testSortThrowsExceptionOnInvalidSortValues() {
        $this->query->sort(['field:foo']);
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Badly formatted sort
     * @expectedExceptionCode 400
     */
    public function testSortThrowsExceptionWhenTheStortStringIsBadlyFormatted() {
        $this->query->sort(['field:asc', '']);
    }
}
