<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

use ImboSDK\EventListener\ImageVariations\Database\DatabaseTests;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;

use function sprintf;

#[CoversClass(SQLite::class)]
#[Group('integration')]
#[RequiresEnvironmentVariable('SQLITE_DSN')]
class SQLiteIntegrationTest extends DatabaseTests
{
    protected function getAdapter(): SQLite
    {
        $dsn = (string) getenv('SQLITE_DSN');

        try {
            $pdo = new PDO($dsn, options: [PDO::ATTR_PERSISTENT => true]);
            $pdo->exec(sprintf('DELETE FROM `%s`', SQLite::IMAGEVARIATIONS_TABLE));
        } catch (PDOException) {
            $this->markTestSkipped('SQLite database is not available.');
        }

        return new SQLite($dsn);
    }
}
