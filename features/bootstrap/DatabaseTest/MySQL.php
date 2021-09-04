<?php declare(strict_types=1);
namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\AdapterTest;
use Imbo\Database\MySQL as DatabaseAdapter;
use Imbo\Database\PDOAdapter;
use PDO;

/**
 * Class for suites that want to use the MySQL database adapter
 */
class MySQL implements AdapterTest {
    static public function setUp(array $config) {
        $pdo = new PDO(
            $config['database.dsn'],
            $config['database.username'],
            $config['database.password'],
        );

        foreach ([PDOAdapter::SHORTURL_TABLE, PDOAdapter::IMAGEINFO_TABLE] as $table) {
            $pdo->query("DELETE FROM `{$table}`");
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
