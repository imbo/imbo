<?php declare(strict_types=1);

namespace Imbo\Behat\StorageTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Storage\GridFS as StorageAdapter;
use MongoDB\Client as MongoClient;

class GridFS implements IntegrationTestAdapter
{
    public function __construct(private string $databaseName, private string $databaseUri)
    {
    }

    public function setUp(): void
    {
        $client = new MongoClient($this->databaseUri);
        $client->selectDatabase($this->databaseName)->drop();
    }

    public function getAdapter(): StorageAdapter
    {
        return new StorageAdapter($this->databaseName, $this->databaseUri);
    }
}
