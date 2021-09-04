<?php declare(strict_types=1);
namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\AdapterTest;
use Imbo\Database\MongoDB as Database;
use MongoDB\Client as MongoClient;

/**
 * Class for suites that want to use the MongoDB database adapter
 */
class MongoDB implements AdapterTest {
    static public function setUp(array $config) {
        $client = new MongoClient(
            $config['database.uri'],
            [
                'username' => $config['database.username'],
                'password' => $config['database.password'],
            ],
        );
        $client->{$config['database.name']}->drop();

        return $config;
    }

    static public function tearDown(array $config) {}

    static public function getAdapter(array $config) : Database {
        return new Database(
            $config['database.name'],
            $config['database.uri'],
            [
                'username' => $config['database.username'],
                'password' => $config['database.password'],
            ],
        );
    }
}
