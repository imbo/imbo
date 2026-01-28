<?php declare(strict_types=1);

namespace Imbo\Behat\DatabaseTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Database\SQLite as DatabaseAdapter;
use PDO;

use function sprintf;

class SQLite implements IntegrationTestAdapter
{
    public function __construct(private string $dsn)
    {
    }

    public function setUp(): void
    {
        $pdo = new PDO($this->dsn, options: [PDO::ATTR_PERSISTENT => true]);

        foreach ([DatabaseAdapter::SHORTURL_TABLE, DatabaseAdapter::IMAGEINFO_TABLE] as $table) {
            $pdo->query(sprintf('DELETE FROM `%s`', $table));
        }
    }

    public function getAdapter(): DatabaseAdapter
    {
        return new DatabaseAdapter($this->dsn);
    }
}
