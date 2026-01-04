<?php declare(strict_types=1);
namespace Imbo\Database;

/**
 * SQLite database driver
 */
class SQLite extends PDOAdapter
{
    /**
     * Class constructor
     *
     * @param string $dsn Database DSN
     * @param array<mixed> $options Driver specific options
     */
    public function __construct(string $dsn, array $options = [])
    {
        parent::__construct($dsn, null, null, $options);
    }

    protected function getIdentifierQuote(): string
    {
        return '`';
    }

    protected function getUniqueConstraintExceptionCode(): int
    {
        return 23000;
    }
}
