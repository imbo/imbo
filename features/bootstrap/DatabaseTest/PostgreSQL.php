<?php declare(strict_types=1);
namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\AdapterTest;
use Imbo\Database\PostgreSQL as DatabaseAdapter;
use PDO;

/**
 * Class for suites that want to use the PostgreSQL database adapter
 */
class PostgreSQL implements AdapterTest {
    static public function setUp(array $config) {
        $pdo = new PDO(
            $config['database.dsn'],
            $config['database.username'],
            $config['database.password'],
        );

        foreach ([DatabaseAdapter::SHORTURL_TABLE, DatabaseAdapter::IMAGEINFO_TABLE] as $table) {
            $pdo->query(sprintf('DELETE FROM "%s"', $table));
        }

        return $config;
    }

    static public function tearDown(array $config) {}

    static public function getAdapter(array $config) : DatabaseAdapter {
        return new DatabaseAdapter(
            $config['database.dsn'],
            $config['database.username'],
            $config['database.password'],
        );
    }
}
