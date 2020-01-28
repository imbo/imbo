<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

use PDO;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Database\Doctrine
 */
class DoctrineTest extends DatabaseTests {
    private $dbPath;

    protected function getAdapter() {
        return new Doctrine([
            'driver' => 'pdo_sqlite',
            'path' => $this->dbPath,
        ]);
    }

    public function setUp() : void {
        $this->dbPath = tempnam(sys_get_temp_dir(), 'imbo-integration-test');

        $pdo = new PDO(sprintf('sqlite:%s', $this->dbPath));
        $pdo->query('
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

    protected function tearDown() : void {
        @unlink($this->dbPath);

        parent::tearDown();
    }
}
