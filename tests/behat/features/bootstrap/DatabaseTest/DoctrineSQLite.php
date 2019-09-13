<?php
namespace ImboBehatFeatureContext\DatabaseTest;

use ImboBehatFeatureContext\AdapterTest;
use Imbo\Database\Doctrine as Database;
use PDO;

/**
 * Class for suites that want to use the Doctrine database adapter with a SQLite database
 */
class DoctrineSQLite implements AdapterTest {
    /**
     * {@inheritdoc}
     */
    static public function setUp(array $config) {
        $path = tempnam(sys_get_temp_dir(), 'imbo_behat_test_database_doctrine_sqlite');

        // Create tmp tables
        $pdo = new PDO(sprintf('sqlite:%s', $path));

        $sqlStatementsFile = sprintf('%s/setup/doctrine.sqlite.sql', $config['project_root']);
        $pdo->exec(file_get_contents($sqlStatementsFile));

        return ['path' => $path];
    }

    static public function tearDown(array $config) {
        unlink($config['path']);
    }

    static public function getAdapter(array $config) : Database {
        return new Database([
            'path' => $config['path'],
            'driver' => 'pdo_sqlite',
        ]);
    }
}
