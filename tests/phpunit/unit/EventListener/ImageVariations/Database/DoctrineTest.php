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

/**
 * @covers Imbo\EventListener\ImageVariations\Database\Doctrine
 * @group unit
 * @group database
 * @group doctrine
 */
class DoctrineTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\EventListener\ImageVariations\Database\Doctrine::__construct
     * @covers Imbo\EventListener\ImageVariations\Database\Doctrine::setConnection
     */
    public function testCanSetConnection() {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $connection->expects($this->once())->method('insert')->will($this->returnValue(false));

        $adapter = new Doctrine([], $connection);

        $this->assertFalse($adapter->storeImageVariationMetadata('key', 'img', 1337, 1942));
    }
}