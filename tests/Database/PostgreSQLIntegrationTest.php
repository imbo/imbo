<?php declare(strict_types=1);
namespace Imbo\Database;

use Imbo\Exception\DatabaseException;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PostgreSQL::class)]
class PostgreSQLIntegrationTest extends DatabaseTests
{
    protected function getAdapter(): PostgreSQL
    {
        try {
            return new PostgreSQL(
                (string) getenv('POSTGRESQL_DSN'),
                (string) getenv('POSTGRESQL_USERNAME'),
                (string) getenv('POSTGRESQL_PASSWORD'),
            );
        } catch (DatabaseException $e) {
            $this->markTestSkipped('Unable to connect to PostgreSQL database: ' . $e->getMessage());
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO(
            (string) getenv('POSTGRESQL_DSN'),
            (string) getenv('POSTGRESQL_USERNAME'),
            (string) getenv('POSTGRESQL_PASSWORD'),
            [
                PDO::ATTR_PERSISTENT => true,
            ],
        );

        $tables = [
            PostgreSQL::IMAGEINFO_TABLE,
            PostgreSQL::SHORTURL_TABLE,
        ];

        foreach ($tables as $table) {
            $pdo->exec(sprintf('DELETE FROM "%s"', $table));
        }
    }
}
