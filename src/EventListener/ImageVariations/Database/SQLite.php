<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

/**
 * SQLite database adapter for the image variations.
 */
class SQLite extends PDOAdapter
{
    /**
     * Class constructor.
     *
     * @param string       $dsn     Database DSN
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
}
