<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Exception\DatabaseException;
use ImboSDK\EventListener\ImageVariations\Database\DatabaseTests;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;

use function sprintf;

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
            $this->markTestSkipped('Unable to connect to PostgreSQL database: '.$e->getMessage());
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO(
            (string) getenv('POSTGRESQL_DSN'),
            (string) getenv('POSTGRESQL_USERNAME'),
            (string) getenv('POSTGRESQL_PASSWORD'),
        );

        $pdo->exec(sprintf('DELETE FROM "%s"', PostgreSQL::IMAGEVARIATIONS_TABLE));
    }
}
