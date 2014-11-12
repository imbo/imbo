<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Database;

use Imbo\Database\Doctrine,
    Doctrine\DBAL\Connection,
    PDOException,
    ReflectionMethod;

/**
 * @covers Imbo\Database\Doctrine
 * @group unit
 * @group database
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
     * Set up the driver
     */
    public function setUp() {
        if (!class_exists('Doctrine\DBAL\Connection')) {
            $this->markTestSkipped('Doctrine is required to run this test');
        }

        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $this->driver = new Doctrine(array(), $this->connection);
    }

    /**
     * Tear down the driver
     */
    public function tearDown() {
        $this->connection = null;
        $this->driver = null;
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsAlreadyConnected() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanConnect() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(false));
        $this->connection->expects($this->once())->method('connect')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanNotConnect() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(false));
        $this->connection->expects($this->once())->method('connect')->will($this->returnValue(false));
        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndConnectThrowsAnException() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(false));
        $this->connection->expects($this->once())->method('connect')->will($this->throwException(new PDOException()));
        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getMetadata() {
        return array(
            'simple key/value' => array(
                array('key' => 'value', 'key2' => 'value2'),
                array('key' => 'value', 'key2' => 'value2'),
            ),
            'numeric array' => array(
                array('key' => array(1, 2, 3)),
                array(
                    'key::0' => 1,
                    'key::1' => 2,
                    'key::2' => 3,
                ),
            ),
            'nested array' => array(
                array('some' => array('key' => array('with' => array('a' => 'value')))),
                array('some::key::with::a' => 'value'),
            ),
            'all sorts of stuff' => array(
                array(
                    'place' => 'Bar & Cigar',
                    'people' => array(
                        array(
                            'name' => 'christer',
                            'beers' => array(
                                array(
                                    'brewery' => 'Nøgne Ø',
                                    'name' => 'Pils',
                                ),
                                array(
                                    'brewery' => 'HaandBryggeriet',
                                    'name' => 'Fyr & Flamme',
                                ),
                            ),
                        ),
                        array(
                            'name' => 'espen',
                            'beers' => array(
                                array(
                                    'brewery' => 'AleSmith',
                                    'name' => 'Speedway Stout',
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'place' => 'Bar & Cigar',
                    'people::0::name' => 'christer',
                    'people::0::beers::0::brewery' => 'Nøgne Ø',
                    'people::0::beers::0::name' => 'Pils',
                    'people::0::beers::1::brewery' => 'HaandBryggeriet',
                    'people::0::beers::1::name' => 'Fyr & Flamme',
                    'people::1::name' => 'espen',
                    'people::1::beers::0::brewery' => 'AleSmith',
                    'people::1::beers::0::name' => 'Speedway Stout',
                ),
            ),
        );
    }

    /**
     * @covers Imbo\Database\Doctrine::normalizeMetadata
     * @dataProvider getMetadata
     */
    public function testCanNormalizeMetadata($denormalizedMetadata, $normalizedMetadata) {
        $method = new ReflectionMethod($this->driver, 'normalizeMetadata');
        $method->setAccessible(true);

        $result = array();
        $method->invokeArgs($this->driver, array(&$denormalizedMetadata, &$result));
        $this->assertSame($result, $normalizedMetadata);
    }

    /**
     * @covers Imbo\Database\Doctrine::denormalizeMetadata
     * @dataProvider getMetadata
     */
    public function testCanDenormalizeMetadata($denormalizedMetadata, $normalizedMetadata) {
        $method = new ReflectionMethod($this->driver, 'denormalizeMetadata');
        $method->setAccessible(true);

        $this->assertSame($denormalizedMetadata, $method->invoke($this->driver, $normalizedMetadata));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Invalid metadata
     * @expectedExceptionCode 400
     * @covers Imbo\Database\Doctrine::normalizeMetadata
     */
    public function testThrowsExceptionWhenKeysContainTheSeparator() {
        $method = new ReflectionMethod($this->driver, 'normalizeMetadata');
        $method->setAccessible(true);

        $result = array();
        $metadata = array(
            'some::key' => 'value',
        );
        $method->invokeArgs($this->driver, array(&$metadata, &$result));
        $this->assertSame($result, $normalizedMetadata);
    }
}
