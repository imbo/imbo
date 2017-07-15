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
    static public function setUp() {
        $path = tempnam(sys_get_temp_dir(), 'imbo_behat_test_database_doctrine_sqlite');

        // Create tmp tables
        $pdo = new PDO(sprintf('sqlite:%s', $path));
        $pdo->query("
            CREATE TABLE IF NOT EXISTS imageinfo (
                id INTEGER PRIMARY KEY NOT NULL,
                user TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                size INTEGER NOT NULL,
                extension TEXT NOT NULL,
                mime TEXT NOT NULL,
                added INTEGER NOT NULL,
                updated INTEGER NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL,
                checksum TEXT NOT NULL,
                originalChecksum TEXT NOT NULL,
                UNIQUE (user,imageIdentifier)
            )
        ");
        $pdo->query("
            CREATE TABLE IF NOT EXISTS metadata (
                id INTEGER PRIMARY KEY NOT NULL,
                imageId KEY INTEGER NOT NULL,
                tagName TEXT NOT NULL,
                tagValue TEXT NOT NULL
            )
        ");
        $pdo->query("
            CREATE TABLE IF NOT EXISTS shorturl (
                shortUrlId TEXT PRIMARY KEY NOT NULL,
                user TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                extension TEXT,
                query TEXT NOT NULL
            )
        ");
        $pdo->query("
            CREATE INDEX shorturlparams ON shorturl (
                user,
                imageIdentifier,
                extension,
                query
            )
        ");

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
