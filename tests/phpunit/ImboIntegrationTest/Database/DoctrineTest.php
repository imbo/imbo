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

use Imbo\Database\Doctrine,
    PDO;

/**
 * @covers Imbo\Database\Doctrine
 * @group integration
 * @group database
 * @group doctrine
 */
class DoctrineTest extends DatabaseTests {
    /**
     * Path to the database file
     *
     * @var string
     */
    private $dbPath;

    /**
     * @see ImboIntegrationTest\Database\DatabaseTests::getAdapter()
     */
    protected function getAdapter() {
        return new Doctrine([
            'path' => $this->dbPath,
            'driver' => 'pdo_sqlite',
        ]);
    }

    public function setUp() {
        if (!extension_loaded('PDO')) {
            $this->markTestSkipped('PDO is required to run this test');
        }

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required to run this test');
        }

        if (!class_exists('Doctrine\DBAL\DriverManager')) {
            $this->markTestSkipped('Doctrine is required to run this test');
        }

        $this->dbPath = tempnam(sys_get_temp_dir(), 'imbo-integration-test');

        // Create tmp tables
        $pdo = new PDO(sprintf('sqlite:%s', $this->dbPath));
        $pdo->query("DROP TABLE IF EXISTS imageinfo");
        $pdo->query("DROP TABLE IF EXISTS metadata");
        $pdo->query("DROP TABLE IF EXISTS shorturl");
        $pdo->query("DROP INDEX IF EXISTS shorturlparams");
        $pdo->query("
            CREATE TABLE imageinfo (
                id INTEGER PRIMARY KEY NOT NULL,
                user TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                size INTEGER NOT NULL,
                extension TEXT NOT NULL,
                mime TEXT NOT NULL,
                added INTEGER NOT NULL,
                updated INTEGER NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL,
                checksum TEXT NOT NULL,
                originalChecksum TEXT NOT NULL,
                UNIQUE (user,imageIdentifier)
            )
        ");
        $pdo->query("
            CREATE TABLE metadata (
                id INTEGER PRIMARY KEY NOT NULL,
                imageId KEY INTEGER NOT NULL,
                tagName TEXT NOT NULL,
                tagValue TEXT NOT NULL
            )
        ");
        $pdo->query("
            CREATE TABLE shorturl (
                shortUrlId TEXT PRIMARY KEY NOT NULL,
                user TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                extension TEXT,
                query TEXT NOT NULL
            )
        ");
        $pdo->query("
            CREATE INDEX shorturlparams ON shorturl (
                user,
                imageIdentifier,
                extension,
                query
            )
        ");

        parent::setUp();
    }

    /**
     * Remove the database file
     */
    public function tearDown() {
        unlink($this->dbPath);
        parent::tearDown();
    }
}
