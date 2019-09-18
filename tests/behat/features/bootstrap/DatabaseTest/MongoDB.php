<?php declare(strict_types=1);
namespace ImboBehatFeatureContext\DatabaseTest;

use ImboBehatFeatureContext\AdapterTest;
use Imbo\Database\MongoDB as Database;
use MongoDB\Client as MongoClient;

/**
 * Class for suites that want to use the MongoDB database adapter
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

    static public function getAdapter(array $config) : Database {
        return new Database([
            'databaseName' => $config['databaseName'],
        ]);
    }
}
