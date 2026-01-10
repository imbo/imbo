<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

use ImboSDK\EventListener\ImageVariations\Database\DatabaseTests;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SQLite::class)]
class SQLiteIntegrationTest extends DatabaseTests
{
    protected function getAdapter(): SQLite
    {
        return new SQLite((string) getenv('SQLITE_DSN'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO(
            dsn: (string) getenv('SQLITE_DSN'),
            options: [PDO::ATTR_PERSISTENT => true],
        );
        $table = SQLite::IMAGEVARIATIONS_TABLE;
        try {
            $pdo->exec("DELETE FROM `{$table}`");
        } catch (PDOException $e) {
            $this->markTestSkipped('SQLite database have not been initialized: '.$e->getMessage());
        }
    }
}
