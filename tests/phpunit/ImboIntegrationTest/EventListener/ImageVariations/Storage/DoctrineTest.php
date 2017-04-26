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
     * Path to the database file
     *
     * @var string
     */
    private $dbPath;

    /**
     * @see ImboIntegrationTest\EventListener\ImageVariations\Storage\StorageTests::getAdapter()
     */
    protected function getAdapter() {
        \PHPUnit_Framework_Error_Deprecated::$enabled = false;

        $adapter = @new Doctrine([
            'path' => $this->dbPath,
            'driver' => 'pdo_sqlite',
        ]);

        \PHPUnit_Framework_Error_Deprecated::$enabled = true;

        return $adapter;
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

        $this->dbPath = tempnam(sys_get_temp_dir(), 'imbo-integration-test');

        // Create tmp tables
        $pdo = new PDO(sprintf('sqlite:%s', $this->dbPath));
        $pdo->query("DROP TABLE IF EXISTS storage_image_variations");
        $pdo->query('
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
     * Remove the database file
     */
    public function tearDown() {
        unlink($this->dbPath);
        parent::tearDown();
    }
}
