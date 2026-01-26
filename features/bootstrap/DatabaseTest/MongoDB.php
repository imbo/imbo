<?php declare(strict_types=1);

namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Database\MongoDB as DatabaseAdapter;
use MongoDB\Client as MongoClient;

class MongoDB implements IntegrationTestAdapter
{
    public function __construct(private string $databaseName, private string $uri, private string $username, private string $password)
    {
    }

    public function setUp(): void
    {
        (new MongoClient($this->uri, [
            'username' => $this->username,
            'password' => $this->password,
        ]))->selectDatabase($this->databaseName)->drop();
    }

    public function getAdapter(): DatabaseAdapter
    {
        return new DatabaseAdapter($this->databaseName, $this->uri, [
            'username' => $this->username,
            'password' => $this->password,
        ]);
    }
}
