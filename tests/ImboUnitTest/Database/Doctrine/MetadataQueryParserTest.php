<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Database\Doctrine;

use Imbo\Database\Doctrine\MetadataQueryParser,
    Doctrine\DBAL\Query\QueryBuilder;

/**
 * @covers Imbo\Database\Doctrine\MetadataQueryParser
 * @group unit
 * @group database
 * @group doctrine
 */
class MetadataQueryParserTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var MetadataQueryParser
     */
    private $parser;

    /**
     * Set up the parser
     */
    public function setUp() {
        if (!class_exists('Doctrine\DBAL\Query\QueryBuilder')) {
            $this->markTestSkipped('Doctrine is required to run this test');
        }

        $this->queryBuilder = new QueryBuilder($this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock());
        $this->queryBuilder->select('*')->from('image', 'i');
        $this->parser = new MetadataQueryParser();
    }

    /**
     * Tear down the parser
     */
    public function tearDown() {
        $this->queryBuilder = null;
        $this->parser = null;
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getMetadataQueries() {
        return array(
            'regular match' => array(
                array('field' => 'value'),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field = :value',
            ),
            'regular match, implicit and' => array(
                array('field' => 'value', 'field2' => 'value2'),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field = :value AND field2 = :value2',
            ),
            'not equals' => array(
                array('field' => array('$ne' => 'value')),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field != :value',
            ),
            'greater than' => array(
                array('field' => array('$gt' => 123)),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field > 123',
            ),
            'greather than or equal' => array(
                array('field' => array('$gte' => 123)),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field >= 123',
            ),
            'less than' => array(
                array('field' => array('$lt' => 123)),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field < 123',
            ),
            'less than or equal' => array(
                array('field' => array('$lte' => 123)),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field <= 123',
            ),
            'in' => array(
                array('field' => array('$in' => array(1, 2, 3))),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field IN (1, 2, 3)',
            ),
            'not in' => array(
                array('field' => array('$nin' => array(1, 2, 3))),
                'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field NOT IN (1, 2, 3)',
            ),
            'explicit and' => array(
                array('$and' => array(array('field' => 123), array('field' => 456))),
               'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field = 123 AND field = 456',
            ),
            'or' => array(
                array('$or' => array(array('field' => 123), array('field' => 456))),
               'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field = 123 OR field = 456',
            ),
            'multiple and/or' => array(
                array('field' => 'value', '$or' => array(array('field2' => 123), array('field3' => 456))),
               'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field = value AND ( field2 = 123 OR field3 = 456 )',
            ),
            'wildcard search' => array(
                array('field' => array('$wildcard' => '*value*')),
               'SELECT * FROM image i LEFT JOIN metadata m ON i.id = m.imageId WHERE field LIKE %value%',
            ),
            //'ALL THE OPERATORS!!1' => array(

            //),
        );
    }

    /**
     * @dataProvider getMetadataQueries
     */
    public function testCanParserQueries(array $metadataQuery, $sql) {
        $this->parser->parseMetadataQuery($metadataQuery, $this->queryBuilder);
        $this->assertSame($sql, (string) $this->queryBuilder, 'The query builder could not generate the correct SQL for the metadata query');
    }
}
