<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Storage;

use Imbo\Storage\Doctrine,
    PDO;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\Storage\Doctrine
 */
class DoctrineTest extends StorageTests {
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @see Imbo\IntegrationTest\Storage\StorageTests::getDriver()
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
            CREATE TABLE storage_images (
                publicKey TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                data BLOB NOT NULL,
                updated INTEGER NOT NULL,
                PRIMARY KEY (publicKey,imageIdentifier)
            )
        ");

        parent::setUp();
    }

    public function tearDown() {
        $this->pdo = null;

        parent::tearDown();
    }
}
