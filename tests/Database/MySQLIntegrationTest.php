<?php declare(strict_types=1);
namespace Imbo\Database;

use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MySQL::class)]
class MySQLIntegrationTest extends DatabaseTests
{
    protected function getAdapter(): MySQL
    {
        return new MySQL(
            (string) getenv('DB_DSN'),
            (string) getenv('DB_USERNAME'),
            (string) getenv('DB_PASSWORD'),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO(
            (string) getenv('DB_DSN'),
            (string) getenv('DB_USERNAME'),
            (string) getenv('DB_PASSWORD'),
            [
                PDO::ATTR_PERSISTENT => true,
            ],
        );

        $tables = [
            MySQL::IMAGEINFO_TABLE,
            MySQL::SHORTURL_TABLE,
        ];

        foreach ($tables as $table) {
            try {
                $pdo->exec("DELETE FROM `{$table}`");
            } catch (PDOException $e) {
                $this->markTestSkipped('MySQL database have not been initialized: ' . $e->getMessage());
            }
        }
    }
}
