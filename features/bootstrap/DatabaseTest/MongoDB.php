<?php declare(strict_types=1);

namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\AdapterTest;
use Imbo\Database\MongoDB as DatabaseAdapter;
use MongoDB\Client as MongoClient;

class MongoDB implements AdapterTest
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

    public static function getAdapter(array $config): DatabaseAdapter
    {
        return new DatabaseAdapter(
            $config['database.name'],
            $config['database.uri'],
            [
                'username' => $config['database.username'],
                'password' => $config['database.password'],
            ],
        );
    }
}
