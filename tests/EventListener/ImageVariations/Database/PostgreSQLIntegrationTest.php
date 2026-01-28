<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

use ImboSDK\EventListener\ImageVariations\Database\DatabaseTests;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;

use function sprintf;

#[CoversClass(PostgreSQL::class)]
#[Group('integration')]
#[RequiresEnvironmentVariable('POSTGRESQL_DSN')]
#[RequiresEnvironmentVariable('POSTGRESQL_USERNAME')]
#[RequiresEnvironmentVariable('POSTGRESQL_PASSWORD')]
class PostgreSQLIntegrationTest extends DatabaseTests
{
    protected function getAdapter(): PostgreSQL
    {
        $dsn = (string) getenv('POSTGRESQL_DSN');
        $username = (string) getenv('POSTGRESQL_USERNAME');
        $password = (string) getenv('POSTGRESQL_PASSWORD');

        try {
            $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_PERSISTENT => true]);
            $pdo->exec(sprintf('DELETE FROM "%s"', PostgreSQL::IMAGEVARIATIONS_TABLE));
        } catch (PDOException) {
            $this->markTestSkipped('PostgreSQL database is not available.');
        }

        return new PostgreSQL($dsn, $username, $password);
    }
}
