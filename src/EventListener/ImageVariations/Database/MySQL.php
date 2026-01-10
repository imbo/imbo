<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

/**
 * MySQL database adapter for the image variations.
 */
class MySQL extends PDOAdapter
{
    protected function getIdentifierQuote(): string
    {
        return '`';
    }
}
