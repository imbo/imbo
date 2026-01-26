<?php declare(strict_types=1);

namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Database\MySQL as DatabaseAdapter;
use PDO;

class MySQL implements IntegrationTestAdapter
{
    public function __construct(private string $dsn, private string $username, private string $password)
    {
    }

    public function setUp(): void
    {
        $pdo = new PDO($this->dsn, $this->username, $this->password, [
            PDO::ATTR_PERSISTENT => true,
        ]);

        foreach ([DatabaseAdapter::SHORTURL_TABLE, DatabaseAdapter::IMAGEINFO_TABLE] as $table) {
            $pdo->query("DELETE FROM `{$table}`");
        }
    }

    public function getAdapter(): DatabaseAdapter
    {
        return new DatabaseAdapter($this->dsn, $this->username, $this->password);
    }
}
