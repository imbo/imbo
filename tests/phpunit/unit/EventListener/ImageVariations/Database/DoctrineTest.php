<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener\ImageVariations\Database;

use Imbo\EventListener\ImageVariations\Database\Doctrine;
use Imbo\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * @covers Imbo\EventListener\ImageVariations\Database\Doctrine
 * @group unit
 * @group database
 * @group doctrine
 */
class DoctrineTest extends TestCase {
    /**
     * @covers Imbo\EventListener\ImageVariations\Database\Doctrine::__construct
     */
    public function testThrowsExceptionWhenUsingPdoInConfiguration() {
        $this->expectExceptionObject(new InvalidArgumentException(
            "The usage of 'pdo' in the configuration for Imbo\EventListener\ImageVariations\Database\Doctrine is not allowed, use 'driver' instead",
            500
        ));
        new Doctrine(['pdo' => new PDO('sqlite::memory:')]);
    }
}
