<?php declare(strict_types=1);

namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Database\MongoDB as DatabaseAdapter;
use MongoDB\Client as MongoClient;

class MongoDB implements IntegrationTestAdapter
{
    public function __construct(private string $databaseName, private string $uri)
    {
    }

    public function setUp(): void
    {
        $client = new MongoClient($this->uri);
        $client->selectDatabase($this->databaseName)->drop();
    }

    public function getAdapter(): DatabaseAdapter
    {
        return new DatabaseAdapter($this->databaseName, $this->uri);
    }
}
