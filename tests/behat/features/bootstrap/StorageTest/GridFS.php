<?php
namespace ImboBehatFeatureContext\StorageTest;

use ImboBehatFeatureContext\AdapterTest;
use Imbo\Storage\GridFS as Storage;
use MongoDB\Client as MongoClient;

/**
 * Class for suites that want to use the GridFS storage adapter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class GridFS implements AdapterTest {
    /**
     * {@inheritdoc}
     */
    static public function setUp(array $config) {
        $databaseName = 'imbo_behat_test_storage';

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
        return new Storage([
            'databaseName' => $config['databaseName'],
        ]);
    }
}
