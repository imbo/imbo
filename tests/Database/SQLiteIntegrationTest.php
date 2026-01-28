<?php declare(strict_types=1);

namespace Imbo\Database;

use ImboSDK\Database\DatabaseTests;
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
        $tables = [
            SQLite::IMAGEINFO_TABLE,
            SQLite::SHORTURL_TABLE,
        ];

        $dsn = (string) getenv('SQLITE_DSN');

        try {
            $pdo = new PDO($dsn, options: [PDO::ATTR_PERSISTENT => true]);

            foreach ($tables as $table) {
                $pdo->exec(sprintf('DELETE FROM `%s`', $table));
            }
        } catch (PDOException) {
            $this->markTestSkipped('SQLite database is not available.');
        }

        return new SQLite($dsn);
    }
}
