<?php declare(strict_types=1);
namespace Imbo\Behat\StorageTest;

use Imbo\Behat\AdapterTest;
use Imbo\Storage\GridFS as Storage;
use MongoDB\Client as MongoClient;

/**
 * Class for suites that want to use the GridFS storage adapter
 */
class GridFS implements AdapterTest {
    /**
     * {@inheritdoc}
     */
    static public function setUp(array $config) {
        $config['databaseName'] = 'imbo_behat_test_storage';

        $client = new MongoClient(
            $config['database.uri'],
            [
                'username' => $config['database.username'],
                'password' => $config['database.password'],
            ],
        );
        $client->{$config['databaseName']}->drop();

        return $config;
    }

    static public function tearDown(array $config) {}

    static public function getAdapter(array $config) : Storage {
        return new Storage(
            $config['databaseName'],
            $config['database.uri'],
            [
                'username' => $config['database.username'],
                'password' => $config['database.password'],
            ],
        );
    }
}
