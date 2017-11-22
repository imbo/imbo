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

use Imbo\Database\Doctrine;
use PDO;

/**
 * @covers Imbo\Database\Doctrine
 * @coversDefaultClass Imbo\Database\Doctrine
 * @group integration
 * @group database
 * @group doctrine
 */
class DoctrineTest extends DatabaseTests {
    /**
     * Path to the SQLite database
     *
     * @var string
     */
    private $dbPath;

    /**
     * Database connection
     *
     * @var PDO
     */
    private $pdo;

    /**
     * {@inheritdoc}
     */
    protected function getAdapter() {
        return new Doctrine([
            'path' => $this->dbPath,
            'driver' => 'pdo_sqlite',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function insertImage(array $image) {
        $stmt = $this->pdo->prepare("
            INSERT INTO imageinfo (
                user, imageIdentifier, size, extension, mime, added, updated, width, height,
                checksum, originalChecksum
            ) VALUES (
                :user, :imageIdentifier, :size, :extension, :mime, :added, :updated, :width,
                :height, :checksum, :originalChecksum
            )
        ");
        $stmt->execute([
            ':user'             => $image['user'],
            ':imageIdentifier'  => $image['imageIdentifier'],
            ':size'             => $image['size'],
            ':extension'        => $image['extension'],
            ':mime'             => $image['mime'],
            ':added'            => $image['added'],
            ':updated'          => $image['updated'],
            ':width'            => $image['width'],
            ':height'           => $image['height'],
            ':checksum'         => $image['checksum'],
            ':originalChecksum' => $image['originalChecksum'],
        ]);
    }

    /**
     * Create the necessary tables for testing
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
        $this->pdo = new PDO(sprintf('sqlite:%s', $this->dbPath));

        $sqlStatementsFile = sprintf('%s/setup/doctrine.sqlite.sql', PROJECT_ROOT);

        $this->pdo->exec(file_get_contents($sqlStatementsFile));
        parent::setUp();
    }

    /**
     * Remove the database file
     */
    public function tearDown() {
        @unlink($this->dbPath);

        parent::tearDown();
    }
}
