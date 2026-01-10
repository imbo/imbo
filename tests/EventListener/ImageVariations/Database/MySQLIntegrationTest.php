<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Exception\DatabaseException;
use ImboSDK\EventListener\ImageVariations\Database\DatabaseTests;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;

use function sprintf;

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
            $this->markTestSkipped('Unable to connect to MySQL database: '.$e->getMessage());
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO(
            (string) getenv('MYSQL_DSN'),
            (string) getenv('MYSQL_USERNAME'),
            (string) getenv('MYSQL_PASSWORD'),
        );

        try {
            $pdo->exec(sprintf('DELETE FROM `%s`', MySQL::IMAGEVARIATIONS_TABLE));
        } catch (PDOException $e) {
            $this->markTestSkipped('MySQL database have not been initialized: '.$e->getMessage());
        }
    }
}
