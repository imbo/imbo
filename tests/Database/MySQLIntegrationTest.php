<?php declare(strict_types=1);
namespace Imbo\Database;

use Imbo\Exception\DatabaseException;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MySQL::class)]
class MySQLIntegrationTest extends DatabaseTests
{
    protected function getAdapter(): MySQL
    {
        try {
            return new MySQL(
                (string) getenv('MYSQL_DSN'),
                (string) getenv('MYSQL_USERNAME'),
                (string) getenv('MYSQL_PASSWORD'),
            );
        } catch (DatabaseException $e) {
            $this->markTestSkipped('Unable to connect to MySQL database: ' . $e->getMessage());
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO(
            (string) getenv('MYSQL_DSN'),
            (string) getenv('MYSQL_USERNAME'),
            (string) getenv('MYSQL_PASSWORD'),
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
