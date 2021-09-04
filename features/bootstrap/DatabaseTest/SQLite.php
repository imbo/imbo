<?php declare(strict_types=1);
namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\AdapterTest;
use Imbo\Database\PDOAdapter;
use Imbo\Database\SQLite as DatabaseAdapter;
use PDO;

/**
 * Class for suites that want to use the SQLite database adapter
 */
class SQLite implements AdapterTest {
    static public function setUp(array $config) {
        $pdo = new PDO($config['database.dsn']);

        foreach ([PDOAdapter::SHORTURL_TABLE, PDOAdapter::IMAGEINFO_TABLE] as $table) {
            $pdo->query("DELETE FROM `{$table}`");
        }

        return $config;
    }

    static public function tearDown(array $config) {}

    static public function getAdapter(array $config) : DatabaseAdapter {
        return new DatabaseAdapter($config['database.dsn']);
    }
}
