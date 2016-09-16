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
    Doctrine\DBAL\Connection;

/**
 * @covers Imbo\Storage\Doctrine
 * @group unit
 * @group storage
 * @group doctrine
 */
class DoctrineTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Doctrine
     */
    private $driver;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $user = 'key';

    /**
     * Set up the driver
     */
    public function setUp() {
        if (!class_exists('Doctrine\DBAL\Connection')) {
            $this->markTestSkipped('Doctrine is required to run this test');
        }

        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $this->driver = new Doctrine([], $this->connection);
    }

    /**
     * Tear down the driver
     */
    public function tearDown() {
        $this->connection = null;
        $this->driver = null;
    }

    /**
     * @covers Imbo\Storage\Doctrine::store
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 500
     */
    public function testStoreExceptionOnInsertFailure() {
        $this->connection->expects($this->once())->method('insert')->will($this->returnValue(0));

        $driverMock = $this->getMockBuilder('Imbo\Storage\Doctrine')
            ->setConstructorArgs(array([], $this->connection))
            ->setMethods(array('imageExists'))
            ->getMock();

        $driverMock->expects($this->once())->method('imageExists')->will($this->returnValue(false));
        $driverMock->store($this->user, 'image_identifier_not_used', 'image_data_not_used');
    }

    /**
     * @covers Imbo\Storage\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsAlreadyConnected() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Storage\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanConnect() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(false));
        $this->connection->expects($this->once())->method('connect')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Storage\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanNotConnect() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(false));
        $this->connection->expects($this->once())->method('connect')->will($this->returnValue(false));
        $this->assertFalse($this->driver->getStatus());
    }
}
