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
        $value = array('id1', 'id2');
        $this->assertSame(array(), $this->query->imageIdentifiers());
        $this->assertSame($this->query, $this->query->imageIdentifiers($value));
        $this->assertSame($value, $this->query->imageIdentifiers());
    }

    /**
     * @covers Imbo\Resource\Images\Query::checksums
     */
    public function testChecksums() {
        $value = array('sum1', 'sum2');
        $this->assertSame(array(), $this->query->checksums());
        $this->assertSame($this->query, $this->query->checksums($value));
        $this->assertSame($value, $this->query->checksums());
    }

    /**
     * @covers Imbo\Resource\Images\Query::originalChecksums
     */
    public function testOriginalChecksums() {
        $value = array('sum1', 'sum2');
        $this->assertSame(array(), $this->query->originalChecksums());
        $this->assertSame($this->query, $this->query->originalChecksums($value));
        $this->assertSame($value, $this->query->originalChecksums());
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getSortData() {
        return array(
            'single field without sort' => array(
                array('field1'),
                array(
                    array(
                        'field' => 'field1',
                        'sort' => 'asc',
                    ),
                ),
            ),
            'single field with sort' => array(
                array('field1:desc'),
                array(
                    array(
                        'field' => 'field1',
                        'sort' => 'desc',
                    ),
                ),
            ),
            'multiple fields' => array(
                array('field1', 'field2:desc', 'field3:asc'),
                array(
                    array(
                        'field' => 'field1',
                        'sort' => 'asc',
                    ),
                    array(
                        'field' => 'field2',
                        'sort' => 'desc',
                    ),
                    array(
                        'field' => 'field3',
                        'sort' => 'asc',
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider getSortData
     * @covers Imbo\Resource\Images\Query::sort
     */
    public function testSort(array $value, $formatted) {
        $this->assertSame(array(), $this->query->sort());
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
        $this->query->sort(array('field:foo'));
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Badly formatted sort
     * @expectedExceptionCode 400
     */
    public function testSortThrowsExceptionWhenTheStortStringIsBadlyFormatted() {
        $this->query->sort(array('field:asc', ''));
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Badly formatted sort
     * @expectedExceptionCode 400
     */
    public function testThrowsAnExceptionOnBadlyFormattedSortData() {
        $this->query->sort(array(''));
    }

    /**
     * @covers Imbo\Resource\Images\Query::metadataQuery
     */
    public function testMetadataQuery() {
        $query = array('name' => array('$in' => array('christer', 'espen')));
        $this->assertSame(array(), $this->query->metadataQuery());
        $this->assertSame($this->query, $this->query->metadataQuery($query));
        $this->assertSame($query, $this->query->metadataQuery());
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getMetadataQueries() {
        return array(
            'already lowercased' => array(
                'original' => array('foo' => 'bar'),
                'expected' => array('foo' => 'bar'),
            ),
            'mixed case' => array(
                'original' => array('Foo' => 'Bar'),
                'expected' => array('foo' => 'bar'),
            ),
            'mixed case, deep array' => array(
                'original' => array('FOO' => 'bar', 'Bar' => array('Key' => array(1, 2, 3, 'ASD', 1.123))),
                'expected' => array('foo' => 'bar', 'bar' => array('key' => array(1, 2, 3, 'asd', 1.123))),
            ),
        );
    }

    /**
     * @dataProvider getMetadataQueries
     */
    public function testLowercasesMetadataQueries($original, $expected) {
        $this->assertSame($this->query, $this->query->metadataQuery($original));
        $this->assertSame($expected, $this->query->metadataQuery());
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Query operator $regex is not supported
     * @expectedExceptionCode 400
     */
    public function testThrowsAnExceptionIfAMetadataQueryContainsAnUnsupportedOperator() {
        $this->query->metadataQuery(array('category' => array('$regex' => '(foo|bar|baz)')));
    }
}
