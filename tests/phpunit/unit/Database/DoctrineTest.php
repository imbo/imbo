<?php declare(strict_types=1);
namespace Imbo\Database;

use Imbo\Exception\DatabaseException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Database\Doctrine
 */
class DoctrineTest extends TestCase {
    private $driver;
    private $connection;
    public function setUp() : void {
        $this->driver = $this
            ->getMockBuilder(Doctrine::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        $this->connection = $this->createMock(Connection::class);
        $this->driver
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenDatabaseIsAlreadyConnected() : void {
        $this->connection
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanConnect() : void {
        $this->connection
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(false);

        $this->connection
            ->expects($this->any())
            ->method('connect')
            ->willReturn(true);

        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanNotConnect() : void {
        $this->connection
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(false);

        $this->connection
            ->expects($this->any())
            ->method('connect')
            ->willReturn(false);

        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndConnectThrowsAnException() : void {
        $this->connection
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(false);

        $this->connection
            ->expects($this->any())
            ->method('connect')
            ->willThrowException(new DBALException());

        $this->assertFalse($this->driver->getStatus());
    }

    public function getMetadata() : array {
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
     * @dataProvider getMetadata
     * @covers ::normalizeMetadata
     */
    public function testCanNormalizeMetadata(array $denormalizedMetadata, array $normalizedMetadata) : void {
        $input = array_map(function(string $value, string $key) : array {
            return [
                'metadata',
                [
                    'imageId'  => 123,
                    'tagName'  => $key,
                    'tagValue' => $value,
                ]
            ];
        }, $normalizedMetadata, array_keys($normalizedMetadata));

        $stmt = $this->createConfiguredMock(PDOStatement::class, [
            'fetch' => ['id' => 123],
            'fetchAll' => [],
        ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('delete')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameters')->willReturnSelf();
        $qb->method('execute')->willReturn($stmt);

        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $this->connection
            ->expects($this->exactly(count($input)))
            ->method('insert')
            ->withConsecutive(...$input);

        $this->driver->updateMetadata('user', 'image id', $denormalizedMetadata);
    }

    /**
     * @covers ::denormalizeMetadata
     * @dataProvider getMetadata
     */
    public function testCanDenormalizeMetadata(array $denormalizedMetadata, array $normalizedMetadata) : void {
        $dbResult = array_map(function(string $value, string $key) : array {
            return [
                'tagName' => $key,
                'tagValue' => $value,
            ];
        }, $normalizedMetadata, array_keys($normalizedMetadata));

        $stmt = $this->createConfiguredMock(PDOStatement::class, [
            'fetch' => ['id' => 123],
            'fetchAll' => $dbResult,
        ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameters')->willReturnSelf();
        $qb->method('execute')->willReturn($stmt);

        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->assertEquals($denormalizedMetadata, $this->driver->getMetadata('user', 'image id'));
    }

    /**
     * @covers ::normalizeMetadata
     */
    public function testThrowsExceptionWhenKeysContainTheSeparator() : void {
        $stmt = $this->createConfiguredMock(PDOStatement::class, [
            'fetch' => ['id' => 123],
            'fetchAll' => [],
        ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameters')->willReturnSelf();
        $qb->method('execute')->willReturn($stmt);

        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->expectExceptionObject(new DatabaseException('Invalid metadata', 400));
        $this->driver->updateMetadata('user', 'image id', ['some::key' => 'value']);
    }
}
