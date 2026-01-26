<?php declare(strict_types=1);

namespace Imbo\Behat\StorageTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Storage\GridFS as StorageAdapter;
use MongoDB\Client as MongoClient;

class GridFS implements IntegrationTestAdapter
{
    public function __construct(private string $databaseName, private string $databaseUri, private string $username, private string $password)
    {
    }

    public function setUp(): void
    {
        (new MongoClient($this->databaseUri, [
            'username' => $this->username,
            'password' => $this->password,
        ]))->selectDatabase($this->databaseName)->drop();
    }

    public function getAdapter(): StorageAdapter
    {
        return new StorageAdapter($this->databaseName, $this->databaseUri, [
            'username' => $this->username,
            'password' => $this->password,
        ]);
    }
}
