<?php declare(strict_types=1);

namespace Imbo\Database;

use ImboSDK\Database\DatabaseTests;
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
        $tables = [
            PostgreSQL::IMAGEINFO_TABLE,
            PostgreSQL::SHORTURL_TABLE,
        ];

        $dsn = (string) getenv('POSTGRESQL_DSN');
        $username = (string) getenv('POSTGRESQL_USERNAME');
        $password = (string) getenv('POSTGRESQL_PASSWORD');

        try {
            $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_PERSISTENT => true]);

            foreach ($tables as $table) {
                $pdo->exec(sprintf('DELETE FROM "%s"', $table));
            }
        } catch (PDOException) {
            $this->markTestSkipped('PostgreSQL database is not available.');
        }

        return new PostgreSQL($dsn, $username, $password);
    }
}
