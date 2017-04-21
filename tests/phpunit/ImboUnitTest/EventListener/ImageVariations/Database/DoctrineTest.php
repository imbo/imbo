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
use Doctrine\DBAL\DriverManager;
use PDO;

/**
 * @covers Imbo\EventListener\ImageVariations\Database\Doctrine
 * @group unit
 * @group database
 * @group doctrine
 */
class DoctrineTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException PHPUnit_Framework_Error_Deprecated
     * @expectedExceptionMessage The usage of pdo in the configuration array for Imbo\EventListener\ImageVariations\Database\Doctrine is deprecated and will be removed in Imbo-3.x
     */
    public function testUsageOfPdoInParametersIsDeprecated() {
        new Doctrine(['pdo' => new PDO('sqlite::memory:')]);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Deprecated
     * @expectedExceptionMessage Specifying a connection instance in Imbo\EventListener\ImageVariations\Database\Doctrine is deprecated and will be removed in Imbo-3.x
     */
    public function testUsageOfConnectionInConstructor() {
        new Doctrine([], DriverManager::getConnection(['pdo' => new PDO('sqlite::memory:')]));
    }
}
