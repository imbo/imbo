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

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\Doctrine
 * @group unit
 * @group storage
 * @group mongodb
 */
class DoctrineTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\EventListener\ImageVariations\Storage\Doctrine::__construct
     * @covers Imbo\EventListener\ImageVariations\Storage\Doctrine::setConnection
     */
    public function testCanSetConnection() {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $connection->expects($this->once())->method('insert')->will($this->returnValue(false));

        $adapter = new Doctrine([], $connection);
        $this->assertFalse($adapter->storeImageVariation('key', 'img', 'blob', 1942));
    }
}
