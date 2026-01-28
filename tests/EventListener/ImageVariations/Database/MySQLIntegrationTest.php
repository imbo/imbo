<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

use ImboSDK\EventListener\ImageVariations\Database\DatabaseTests;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;

use function sprintf;

#[CoversClass(MySQL::class)]
#[Group('integration')]
#[RequiresEnvironmentVariable('MYSQL_DSN')]
#[RequiresEnvironmentVariable('MYSQL_USERNAME')]
#[RequiresEnvironmentVariable('MYSQL_PASSWORD')]
class MySQLIntegrationTest extends DatabaseTests
{
    protected function getAdapter(): MySQL
    {
        $dsn = (string) getenv('MYSQL_DSN');
        $username = (string) getenv('MYSQL_USERNAME');
        $password = (string) getenv('MYSQL_PASSWORD');

        try {
            $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_PERSISTENT => true]);
            $pdo->exec(sprintf('DELETE FROM `%s`', MySQL::IMAGEVARIATIONS_TABLE));
        } catch (PDOException) {
            $this->markTestSkipped('MySQL database is not available.');
        }

        return new MySQL($dsn, $username, $password);
    }
}
