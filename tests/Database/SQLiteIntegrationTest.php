<?php declare(strict_types=1);
namespace Imbo\Database;

use ImboSDK\Database\DatabaseTests;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SQLite::class)]
class SQLiteIntegrationTest extends DatabaseTests
{
    private PDO $pdo;

    protected function getAdapter(): SQLite
    {
        return new SQLite((string) getenv('SQLITE_DSN'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO(
            dsn: (string) getenv('SQLITE_DSN'),
            options: [PDO::ATTR_PERSISTENT => true],
        );

        $tables = [
            SQLite::IMAGEINFO_TABLE,
            SQLite::SHORTURL_TABLE,
        ];

        foreach ($tables as $table) {
            try {
                $this->pdo->exec("DELETE FROM `{$table}`");
            } catch (PDOException $e) {
                $this->markTestSkipped('SQLite database have not been initialized: ' . $e->getMessage());
            }
        }
    }
}
