<?php declare(strict_types=1);
namespace ImboUnitTest\EventListener\ImageVariations\Database;

use Imbo\EventListener\ImageVariations\Database\Doctrine;
use Imbo\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Database\Doctrine
 */
class DoctrineTest extends TestCase {
    /**
     * @covers ::__construct
     */
    public function testThrowsExceptionWhenUsingPdoInConfiguration() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            "The usage of 'pdo' in the configuration for Imbo\EventListener\ImageVariations\Database\Doctrine is not allowed, use 'driver' instead",
            500
        ));
        new Doctrine(['pdo' => new PDO('sqlite::memory:')]);
    }
}
