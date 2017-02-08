<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Database;

use Cassandra as CassandraLib,
    Cassandra\SimpleStatement,
    Imbo\Database\Cassandra,
    Imbo\Exception\DatabaseException,
    Imbo\Resource\Images\Query;

/**
 * @covers Imbo\Database\Cassandra
 * @group integration
 * @group database
 * @group doctrine
 */
class CassandraTest extends DatabaseTests {
    private $cassandra;

    /**
     * @see ImboIntegrationTest\Database\DatabaseTests::getAdapter()
     */
    protected function getAdapter() {
        return new Cassandra([
            'session' => $this->cassandra,
        ]);
    }


    public function setUp() {
        if (!extension_loaded('Cassandra')) {
            $this->markTestSkipped('Cassandra\'s php-driver is required to run this test');
        }

        $host = !empty($GLOBALS['CASSANDRA_HOST']) ? $GLOBALS['CASSANDRA_HOST'] : '127.0.0.1';
        $port = !empty($GLOBALS['CASSANDRA_PORT']) ? $GLOBALS['CASSANDRA_PORT'] : 9042;

        // Create tmp tables
        $server = CassandraLib::cluster()->withContactPoints($host)->withPort($port)->build();
        $this->cassandra = $server->connect();
        $this->cassandra->execute(new SimpleStatement("CREATE KEYSPACE IF NOT EXISTS imbo_integration_tests WITH replication = {'class': 'SimpleStrategy', 'replication_factor': 1}"));
        $this->cassandra = $server->connect('imbo_integration_tests');

        $this->cassandra->execute(new SimpleStatement("
            CREATE TABLE IF NOT EXISTS imageinfo (
                user TEXT,
                imageIdentifier TEXT,
                size INT,
                extension TEXT,
                mime TEXT,
                added INT,
                updated INT,
                width INT,
                height INT,
                checksum TEXT,
                originalChecksum TEXT,
                metadata map<text, text>,
                PRIMARY KEY (user, imageIdentifier)
            ) 
        "));

        $this->cassandra->execute(new SimpleStatement("
            CREATE TABLE IF NOT EXISTS shorturl_user (
                user TEXT,
                imageIdentifier TEXT,
                shortUrlId TEXT,
                PRIMARY KEY ((user, imageIdentifier), shortUrlId)
            )
        "));

        $this->cassandra->execute(new SimpleStatement("
            CREATE TABLE IF NOT EXISTS shorturl (
                shortUrlId TEXT,
                extension TEXT,
                query TEXT,
                imageIdentifier TEXT,
                user TEXT,
                PRIMARY KEY (shortUrlId, extension, query)
            )
        "));

        $this->cassandra->execute(new SimpleStatement("
            CREATE TABLE IF NOT EXISTS usermeta (
                user TEXT,
                last_updated TIMESTAMP,
                PRIMARY KEY (user)                   
            )
        "));

        parent::setUp();
    }

    public function tearDown() {
        if ($this->cassandra instanceof CassandraLib\Session) {
            /*$this->cassandra->execute(new SimpleStatement("DROP TABLE IF EXISTS imageinfo"));
            $this->cassandra->execute(new SimpleStatement("DROP TABLE IF EXISTS shorturl"));
            $this->cassandra->execute(new SimpleStatement("DROP TABLE IF EXISTS shorturl_user"));
            $this->cassandra->execute(new SimpleStatement("DROP TABLE IF EXISTS usermeta"));
            /* */

            $this->cassandra->execute(new SimpleStatement("TRUNCATE TABLE imageinfo"));
            $this->cassandra->execute(new SimpleStatement("TRUNCATE TABLE shorturl"));
            $this->cassandra->execute(new SimpleStatement("TRUNCATE TABLE usermeta"));
            /* */
        }

        $this->cassandra = null;
        parent::tearDown();
    }

    public function testMetadataWithNestedArraysIsRepresetedCorrectly() {
        $this->markTestSkipped('Cassandra adapter does not retain native types');
    }

    public function testMetadataWithNestedArraysIsRepresetedCorrectlyWhenFetchingMultipleImages() {
        $this->markTestSkipped('Cassandra adapter does not retain native types');
    }

    public function testGetImagesWithNoQuery() {
        $this->markTestSkipped('Cassandra adapter does not support images querying');
    }

    public function testGetImagesWithStartAndEndTimestamps() {
        $this->markTestSkipped('Cassandra adapter does not support querying by start and end times.');
    }

    public function testGetImagesAndReturnMetadata() {
        $this->markTestSkipped('Cassandra adapter does not support images querying');
    }

    public function testGetImagesWithPageAndLimit($page = null, $limit = null, array $imageIdentifiers = []) {
        $this->markTestSkipped('Cassandra adapter does not support images querying');
    }

    public function testGetImagesReturnsImagesOnlyForSpecifiedUsers() {
        $this->markTestSkipped('Cassandra adapter does not support images querying');
    }

    public function testGetImagesReturnsImagesWithDateTimeInstances() {
        $this->markTestSkipped('Cassandra adapter does not support images querying');
    }

    public function testCanFilterOnImageIdentifiers() {
        $this->markTestSkipped('Cassandra adapter does not support images querying');
    }

    public function testCanFilterOnChecksums() {
        $this->markTestSkipped('Cassandra adapter does not support images querying');
    }

    /**
     * @dataProvider getSortData
     */
    public function testCanSortImages(array $sort = null, $field, array $values) {
        $this->markTestSkipped('Cassandra adapter does not support images querying');
    }
}
