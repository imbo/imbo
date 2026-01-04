<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SQLite::class)]
class SQLiteIntegrationTest extends DatabaseTests
{
    protected function getAdapter(): SQLite
    {
        return new SQLite((string) getenv('DB_DSN'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO(
            dsn: (string) getenv('DB_DSN'),
            options: [PDO::ATTR_PERSISTENT => true],
        );
        $table = SQLite::IMAGEVARIATIONS_TABLE;
        $pdo->exec("DELETE FROM `{$table}`");
    }
}
