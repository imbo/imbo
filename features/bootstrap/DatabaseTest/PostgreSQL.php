<?php declare(strict_types=1);

namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Database\PostgreSQL as DatabaseAdapter;
use PDO;

use function sprintf;

class PostgreSQL implements IntegrationTestAdapter
{
    public function __construct(private string $dsn, private string $username, private string $password)
    {
    }

    public function setUp(): void
    {
        $pdo = new PDO($this->dsn, $this->username, $this->password, [PDO::ATTR_PERSISTENT => true]);

        foreach ([DatabaseAdapter::SHORTURL_TABLE, DatabaseAdapter::IMAGEINFO_TABLE] as $table) {
            $pdo->query(sprintf('DELETE FROM "%s"', $table));
        }
    }

    public function getAdapter(): DatabaseAdapter
    {
        return new DatabaseAdapter($this->dsn, $this->username, $this->password);
    }
}
