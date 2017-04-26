<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\Doctrine;
use PDO;
use Doctrine\DBAL\DriverManager;

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\Doctrine
 * @group unit
 * @group storage
 * @group mongodb
 */
class DoctrineTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException PHPUnit_Framework_Error_Deprecated
     * @expectedExceptionMessage The Imbo\EventListener\ImageVariations\Storage\Doctrine adapter is deprecated and will be removed in Imbo-3.x
     */
    public function testAdapterIsDeprecated() {
        new Doctrine([]);
    }
}
