<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener\ImageVariations\Database;

use Imbo\EventListener\ImageVariations\Database\Doctrine,
    PDO;

/**
 * @covers Imbo\EventListener\ImageVariations\Database\Doctrine
 * @group integration
 * @group database
 * @group doctrine
 */
class DoctrineTest extends DatabaseTests {
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @see ImboIntegrationTest\EventListener\ImageVariations\Database\DatabaseTests::getAdapter()
     */
    protected function getAdapter() {
        return new Doctrine([
            'pdo' => $this->pdo,
        ]);
    }

    /**
     * Make sure we have the PDO and pdo_sqlite extension available and create a new in-memory
     * table for every test
     */
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
        $this->pdo->query('
            CREATE TABLE IF NOT EXISTS imagevariations (
                user TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL,
                added INTEGER NOT NULL,
                PRIMARY KEY (user,imageIdentifier,width)
            )
        ');

        parent::setUp();
    }

    /**
     * Clean up after each run
     */
    public function tearDown() {
        if ($this->pdo) {
            $this->pdo->query('DROP TABLE IF EXISTS imagevariations');
            $this->pdo = null;
        }

        parent::tearDown();
    }
}
