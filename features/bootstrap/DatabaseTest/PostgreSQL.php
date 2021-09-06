<?php declare(strict_types=1);
namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\AdapterTest;
use Imbo\Database\PostgreSQL as DatabaseAdapter;
use PDO;

class PostgreSQL implements AdapterTest
{
    public static function setUp(array $config): array
    {
        $pdo = new PDO(
            $config['database.dsn'],
            $config['database.username'],
            $config['database.password'],
            [
                PDO::ATTR_PERSISTENT => true,
            ],
        );

        foreach ([DatabaseAdapter::SHORTURL_TABLE, DatabaseAdapter::IMAGEINFO_TABLE] as $table) {
            $pdo->query(sprintf('DELETE FROM "%s"', $table));
        }

        return $config;
    }

    public static function tearDown(array $config): void
    {
    }

    public static function getAdapter(array $config): DatabaseAdapter
    {
        return new DatabaseAdapter(
            $config['database.dsn'],
            $config['database.username'],
            $config['database.password'],
        );
    }
}
