<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\Doctrine,
    PDO;

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\Doctrine
 * @group integration
 * @group storage
 * @group mongodb
 */
class DoctrineTest extends StorageTests {
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @see ImboIntegrationTest\EventListener\ImageVariations\Storage\StorageTests::getAdapter()
     */
    protected function getAdapter() {
        return new Doctrine([
            'pdo' => $this->pdo,
        ]);
    }

    /**
     * Make sure we have the PDO and pdo_sqlite extension loaded, and Doctrine is installed
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
            CREATE TABLE storage_image_variations (
                user TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                width INTEGER NOT NULL,
                data BLOB NOT NULL,
                PRIMARY KEY (user,imageIdentifier,width)
            )
        ');

        parent::setUp();
    }

    /**
     * Reset PDO instance after each test
     */
    public function tearDown() {
        $this->pdo = null;

        parent::tearDown();
    }
}
