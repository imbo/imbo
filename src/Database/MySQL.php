<?php declare(strict_types=1);
namespace Imbo\Database;

/**
 * MySQL database driver
 */
class MySQL extends PDOAdapter
{
    protected function getIdentifierQuote(): string
    {
        return '`';
    }

    protected function getUniqueConstraintExceptionCode(): int
    {
        return 23000;
    }
}
