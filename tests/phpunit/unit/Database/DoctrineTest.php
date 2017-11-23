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

use Imbo\Database\Doctrine;
use Imbo\Exception\DatabaseException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use PDOException;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Database\Doctrine
 * @group unit
 * @group database
 * @group doctrine
 */
class DoctrineTest extends TestCase {
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
        $this->driver = $this->getMockBuilder(Doctrine::class)
                             ->disableOriginalConstructor()
                             ->setMethods(['getConnection'])
                             ->getMock();

        $this->connection = $this->createMock(Connection::class);
        $this->driver->expects($this->any())
                     ->method('getConnection')
                     ->willReturn($this->connection);
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsAlreadyConnected() {
        $this->connection->expects($this->any())
                         ->method('isConnected')
                         ->willReturn(true);

        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanConnect() {
        $this->connection->expects($this->any())
                         ->method('isConnected')
                         ->willReturn(false);

        $this->connection->expects($this->any())
                         ->method('connect')
                         ->willReturn(true);

        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanNotConnect() {
        $this->connection->expects($this->any())
                         ->method('isConnected')
                         ->will($this->returnValue(false));

        $this->connection->expects($this->any())
                         ->method('connect')
                         ->will($this->returnValue(false));

        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndConnectThrowsAnException() {
        $this->connection->expects($this->any())
                         ->method('isConnected')
                         ->will($this->returnValue(false));

        $this->connection->expects($this->any())
                         ->method('connect')
                         ->will($this->throwException(new DBALException()));

        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getMetadata() {
        return [
            'simple key/value' => [
                ['key' => 'value', 'key2' => 'value2'],
                ['key' => 'value', 'key2' => 'value2'],
            ],
            'numeric array' => [
                ['key' => [1, 2, 3]],
                [
                    'key::0' => 1,
                    'key::1' => 2,
                    'key::2' => 3,
                ],
            ],
            'nested array' => [
                ['some' => ['key' => ['with' => ['a' => 'value']]]],
                ['some::key::with::a' => 'value'],
            ],
            'all sorts of stuff' => [
                [
                    'place' => 'Bar & Cigar',
                    'people' => [
                        [
                            'name' => 'christer',
                            'beers' => [
                                [
                                    'brewery' => 'Nøgne Ø',
                                    'name' => 'Pils',
                                ],
                                [
                                    'brewery' => 'HaandBryggeriet',
                                    'name' => 'Fyr & Flamme',
                                ],
                            ],
                        ],
                        [
                            'name' => 'espen',
                            'beers' => [
                                [
                                    'brewery' => 'AleSmith',
                                    'name' => 'Speedway Stout',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'place' => 'Bar & Cigar',
                    'people::0::name' => 'christer',
                    'people::0::beers::0::brewery' => 'Nøgne Ø',
                    'people::0::beers::0::name' => 'Pils',
                    'people::0::beers::1::brewery' => 'HaandBryggeriet',
                    'people::0::beers::1::name' => 'Fyr & Flamme',
                    'people::1::name' => 'espen',
                    'people::1::beers::0::brewery' => 'AleSmith',
                    'people::1::beers::0::name' => 'Speedway Stout',
                ],
            ],
        ];
    }

    /**
     * @covers Imbo\Database\Doctrine::normalizeMetadata
     * @dataProvider getMetadata
     */
    public function testCanNormalizeMetadata($denormalizedMetadata, $normalizedMetadata) {
        $method = new ReflectionMethod($this->driver, 'normalizeMetadata');
        $method->setAccessible(true);

        $result = [];
        $method->invokeArgs($this->driver, [&$denormalizedMetadata, &$result]);
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
     * @covers Imbo\Database\Doctrine::normalizeMetadata
     */
    public function testThrowsExceptionWhenKeysContainTheSeparator() {
        $method = new ReflectionMethod($this->driver, 'normalizeMetadata');
        $method->setAccessible(true);

        $result = [];
        $metadata = [
            'some::key' => 'value',
        ];
        $this->expectExceptionObject(new DatabaseException('Invalid metadata', 400));
        $method->invokeArgs($this->driver, [&$metadata, &$result]);
    }
}
