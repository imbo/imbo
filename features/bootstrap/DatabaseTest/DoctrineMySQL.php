<?php declare(strict_types=1);
namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\AdapterTest;
use Imbo\Database\Doctrine as Database;
use PDO;

/**
 * Class for suites that want to use the Doctrine database adapter with a MySQL database
 */
class DoctrineMySQL implements AdapterTest {
    /**
     * {@inheritdoc}
     */
    static public function setUp(array $config) {
        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;dbname=%s;port=%d',
                $config['database.hostname'],
                $config['database.database'],
                $config['database.port'],
            ),
            $config['database.username'],
            $config['database.password'],
        );

        $sqlStatementsFile = sprintf('%s/setup/doctrine.mysql.sql', $config['project_root']);

        array_map(function($query) use ($pdo) {
            $pdo->query($query);
        }, explode("\n\n", file_get_contents($sqlStatementsFile)));

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    static public function tearDown(array $config) {
        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;dbname=%s;port=%d',
                $config['database.hostname'],
                $config['database.database'],
                $config['database.port'],
            ),
            $config['database.username'],
            $config['database.password'],
        );
        $pdo->query('DROP TABLE IF EXISTS `imageinfo`, `imagevariations`, `metadata`, `shorturl`');
    }

    static public function getAdapter(array $config) : Database {
        return new Database([
            'dbname'   => $config['database.database'],
            'user'     => $config['database.username'],
            'password' => $config['database.password'],
            'host'     => $config['database.hostname'],
            'port'     => $config['database.port'],
            'driver'   => 'pdo_mysql',
        ]);
    }
}
