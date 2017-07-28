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
use Imbo\Database\MongoDB as Database;
use MongoDB\Client as MongoClient;

/**
 * Class for suites that want to use the MongoDB database adapter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class MongoDB implements AdapterTest {
    /**
     * {@inheritdoc}
     */
    static public function setUp(array $config) {
        $databaseName = 'imbo_behat_test_database';

        self::removeDatabase($databaseName);

        return ['databaseName' => $databaseName];
    }

    /**
     * {@inheritdoc}
     */
    static public function tearDown(array $config) {
        self::removeDatabase($config['databaseName']);
    }

    /**
     * Remove the test database
     *
     * @param string $databaseName Name of the database to drop
     */
    static private function removeDatabase($databaseName) {
        (new MongoClient())->{$databaseName}->drop();
    }

    /**
     * {@inheritdoc}
     */
    static public function getAdapter(array $config) {
        return new Database([
            'databaseName' => $config['databaseName'],
        ]);
    }
}
