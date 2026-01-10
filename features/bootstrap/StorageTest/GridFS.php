<?php declare(strict_types=1);

namespace Imbo\Behat\StorageTest;

use Imbo\Behat\AdapterTest;
use Imbo\Storage\GridFS as StorageAdapter;
use MongoDB\Client as MongoClient;

class GridFS implements AdapterTest
{
    public static function setUp(array $config): array
    {
        $client = new MongoClient(
            $config['database.uri'],
            [
                'username' => $config['database.username'],
                'password' => $config['database.password'],
            ],
        );
        $client->selectDatabase($config['database.name'])->drop();

        return $config;
    }

    public static function tearDown(array $config): void
    {
    }

    public static function getAdapter(array $config): StorageAdapter
    {
        return new StorageAdapter(
            $config['database.name'],
            $config['database.uri'],
            [
                'username' => $config['database.username'],
                'password' => $config['database.password'],
            ],
        );
    }
}
