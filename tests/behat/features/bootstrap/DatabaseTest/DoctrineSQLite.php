<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboBehatFeatureContext\DatabaseTest;

use ImboBehatFeatureContext\AdapterTest;
use Imbo\Database\Doctrine as Database;
use PDO;

/**
 * Class for suites that want to use the Doctrine database adapter with a SQLite database
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
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

    /**
     * {@inheritdoc}
     */
    static public function tearDown(array $config) {
        unlink($config['path']);
    }

    /**
     * {@inheritdoc}
     */
    static public function getAdapter(array $config) {
        return new Database([
            'path' => $config['path'],
            'driver' => 'pdo_sqlite',
        ]);
    }
}
