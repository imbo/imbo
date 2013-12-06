<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Database;

use Imbo\Database\Doctrine,
    PDO;

/**
 * @group integration
 * @group database
 */
class DoctrineTest extends DatabaseTests {
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @see Imbo\IntegrationTest\Database\DatabaseTests::getDriver()
     */
    protected function getDriver() {
        return new Doctrine(array(
            'pdo' => $this->pdo,
        ));
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

        // Create tmp tables
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->query("
            CREATE TABLE IF NOT EXISTS imageinfo (
                id INTEGER PRIMARY KEY NOT NULL,
                publicKey TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                size INTEGER NOT NULL,
                extension TEXT NOT NULL,
                mime TEXT NOT NULL,
                added INTEGER NOT NULL,
                updated INTEGER NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL,
                checksum TEXT NOT NULL,
                UNIQUE (publicKey,imageIdentifier)
            )
        ");
        $this->pdo->query("
            CREATE TABLE IF NOT EXISTS metadata (
                id INTEGER PRIMARY KEY NOT NULL,
                imageId KEY INTEGER NOT NULL,
                tagName TEXT NOT NULL,
                tagValue TEXT NOT NULL
            )
        ");
        $this->pdo->query("
            CREATE TABLE IF NOT EXISTS shorturl (
                shortUrlId TEXT PRIMARY KEY NOT NULL,
                publicKey TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                extension TEXT,
                query TEXT NOT NULL
            )
        ");
        $this->pdo->query("
            CREATE INDEX shorturlparams ON shorturl (
                publicKey,
                imageIdentifier,
                extension,
                query
            )
        ");

        parent::setUp();
    }

    public function tearDown() {
        $this->pdo = null;

        parent::tearDown();
    }
}
