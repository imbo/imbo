<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

/**
 * PostgreSQL database adapter for the image variations
 */
class PostgreSQL extends PDOAdapter
{
    protected function getIdentifierQuote(): string
    {
        return '"';
    }
}
