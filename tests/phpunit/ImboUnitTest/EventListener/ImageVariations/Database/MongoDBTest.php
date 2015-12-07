<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener\ImageVariations\Database;

use Imbo\EventListener\ImageVariations\Database\MongoDB,
    MongoDB\Driver\Manager as DriverManager,
    MongoDB\Collection,
    MongoDB\Driver\Exception\RuntimeException as RuntimeException;

/**
 * @covers Imbo\EventListener\ImageVariations\Database\MongoDB
 * @group unit
 * @group database
 * @group mongodb
 */
class MongoDBTest extends \PHPUnit_Framework_TestCase {
    private $databaseName = 'imboUnitTestDatabase';

    protected function setUp() {
        if (!class_exists('MongoClient')) {
            $this->markTestSkipped('pecl/mongo >= 1.3.0 is required to run this test');
        }
    }

    /**
     * @covers Imbo\EventListener\ImageVariations\Database\MongoDB::__construct
     * @covers Imbo\EventListener\ImageVariations\Database\MongoDB::storeImageVariationMetadata
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to save image variation data
     * @expectedExceptionCode 500
     */
    public function testInsertFailureThrowsDatabaseException() {
        $manager = new DriverManager('mongodb://localhost');
        $collection = $this->getMockBuilder('MongoDB\Collection')->disableOriginalConstructor()->getMock();

        $collection
            ->expects($this->once())
            ->method('insertOne')
            ->will($this->throwException(new RuntimeException()));

        $adapter = new MongoDB([
            'databaseName' => $this->databaseName,
        ], $manager, $collection);

        $adapter->storeImageVariationMetadata('key', 'id', 700, 700);
    }
}
