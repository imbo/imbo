<?php declare(strict_types=1);
namespace Imbo\Database;

/**
 * PostgreSQL database driver
 */
class PostgreSQL extends PDOAdapter
{
    protected function getIdentifierQuote(): string
    {
        return '"';
    }

    protected function getUniqueConstraintExceptionCode(): int
    {
        return 23505;
    }
}
