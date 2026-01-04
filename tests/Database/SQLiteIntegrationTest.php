<?php declare(strict_types=1);
namespace Imbo\Database;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SQLite::class)]
class SQLiteIntegrationTest extends DatabaseTests
{
    private PDO $pdo;

    protected function getAdapter(): SQLite
    {
        return new SQLite((string) getenv('DB_DSN'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO(
            dsn: (string) getenv('DB_DSN'),
            options: [PDO::ATTR_PERSISTENT => true],
        );

        $tables = [
            SQLite::IMAGEINFO_TABLE,
            SQLite::SHORTURL_TABLE,
        ];

        foreach ($tables as $table) {
            $this->pdo->exec("DELETE FROM `{$table}`");
        }
    }
}
