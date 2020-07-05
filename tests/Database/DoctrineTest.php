<?php declare(strict_types=1);
namespace Imbo\Database;

use Imbo\Exception\DatabaseException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

/**
 * @coversDefaultClass Imbo\Database\Doctrine
 */
class DoctrineTest extends DatabaseTests {
    private Doctrine $driver;
    private Connection $connection;
    private string $dbPath;
    private PDO $pdo;

    protected function insertImage(array $image) : void {
        $stmt = $this->pdo->prepare("
            INSERT INTO imageinfo (
                user, imageIdentifier, size, extension, mime, added, updated, width, height,
                checksum, originalChecksum
            ) VALUES (
                :user, :imageIdentifier, :size, :extension, :mime, :added, :updated, :width,
                :height, :checksum, :originalChecksum
            )
        ");
        $stmt->execute([
            ':user'             => $image['user'],
            ':imageIdentifier'  => $image['imageIdentifier'],
            ':size'             => $image['size'],
            ':extension'        => $image['extension'],
            ':mime'             => $image['mime'],
            ':added'            => $image['added'],
            ':updated'          => $image['updated'],
            ':width'            => $image['width'],
            ':height'           => $image['height'],
            ':checksum'         => $image['checksum'],
            ':originalChecksum' => $image['originalChecksum'],
        ]);
    }

    protected function getAdapter() : Doctrine {
        return new Doctrine([
            'path' => $this->dbPath,
            'driver' => 'pdo_sqlite',
        ]);
    }

    public function setUp() : void {
        if (!extension_loaded(PDO::class)) {
            $this->markTestSkipped('PDO is required to run this test');
        }

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required to run this test');
        }

        if (!class_exists(DriverManager::class)) {
            $this->markTestSkipped('Doctrine is required to run this test');
        }

        $this->dbPath = tempnam(sys_get_temp_dir(), 'imbo-integration-test');
        $this->pdo = new PDO(sprintf('sqlite:%s', $this->dbPath));

        $sqlStatementsFile = sprintf('%s/setup/doctrine.sqlite.sql', PROJECT_ROOT);

        $this->pdo->exec(file_get_contents($sqlStatementsFile));

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

            parent::setUp();
    }

    protected function tearDown() : void {
        @unlink($this->dbPath);

        parent::tearDown();
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenDatabaseIsAlreadyConnected() : void {
        $this->connection
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);

        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanConnect() : void {
        $this->connection
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $this->connection
            ->expects($this->once())
            ->method('connect')
            ->willReturn(true);

        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanNotConnect() : void {
        $this->connection
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $this->connection
            ->expects($this->once())
            ->method('connect')
            ->willReturn(false);

        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndConnectThrowsAnException() : void {
        $this->connection
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $this->connection
            ->expects($this->once())
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
