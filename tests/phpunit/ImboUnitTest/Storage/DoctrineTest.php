<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Storage;

use Imbo\Storage\Doctrine,
    Doctrine\DBAL\DriverManager,
    PDO;

/**
 * @covers Imbo\Storage\Doctrine
 * @group unit
 * @group storage
 * @group doctrine
 */
class DoctrineTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException PHPUnit_Framework_Error_Deprecated
     * @expectedExceptionMessage The usage of pdo in the configuration array for Imbo\Storage\Doctrine is deprecated and will be removed in Imbo-3.x
     */
    public function testUsageOfPdoInParametersIsDeprecated() {
        new Doctrine(['pdo' => new PDO('sqlite::memory:')]);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Deprecated
     * @expectedExceptionMessage Specifying a connection instance in Imbo\Storage\Doctrine is deprecated and will be removed in Imbo-3.x
     */
    public function testUsageOfConnectionInConstructor() {
        new Doctrine([], DriverManager::getConnection(['pdo' => new PDO('sqlite::memory:')]));
    }
}
