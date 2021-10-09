<?php declare(strict_types=1);
namespace Imbo\Model;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\Stats
 */
class StatsTest extends TestCase
{
    private $model;

    public function setUp(): void
    {
        $this->model = new Stats();
    }

    public function getNumUsers(): array
    {
        return [
            [0],
            [1],
            [2],
            [4],
        ];
    }

    public function getNumImages(): array
    {
        return [
            [0],
            [2],
            [44],
            [14],
        ];
    }

    public function getNumBytes(): array
    {
        return [
            [0],
            [1349],
            [100114],
            [1000],
        ];
    }

    /**
     * @dataProvider getNumUsers
     * @covers ::setNumUsers
     * @covers ::getNumUsers
     */
    public function testCanSetAndGetNumberOfUsers(int $users): void
    {
        $this->model->setNumUsers($users);
        $this->assertSame($users, $this->model->getNumUsers());
    }

    /**
     * @dataProvider getNumImages
     * @covers ::setNumImages
     * @covers ::getNumImages
     */
    public function testCanSetAndGetAmountOfImages(int $images): void
    {
        $this->model->setNumImages($images);
        $this->assertSame($images, $this->model->getNumImages());
    }

    /**
     * @dataProvider getNumBytes
     * @covers ::setNumBytes
     * @covers ::getNumBytes
     */
    public function testCanSetAndGetAmountOfBytes(int $bytes): void
    {
        $this->model->setNumBytes($bytes);
        $this->assertSame($bytes, $this->model->getNumBytes());
    }

    /**
     * @covers ::getCustomStats
     * @covers ::offsetExists
     * @covers ::offsetSet
     * @covers ::offsetGet
     * @covers ::offsetUnset
     */
    public function testSupportsCustomStats(): void
    {
        $this->assertSame([], $this->model->getCustomStats());

        $this->model['foo'] = 'bar';
        $this->model['bar'] = 'foo';

        $this->assertSame(['foo' => 'bar', 'bar' => 'foo'], $this->model->getCustomStats());

        $this->assertTrue(isset($this->model['bar']));
        $this->assertSame('foo', $this->model['bar']);
        unset($this->model['bar']);
        $this->assertFalse(isset($this->model['bar']));

        $this->assertSame(['foo' => 'bar'], $this->model->getCustomStats());
    }

    /**
     * @covers ::offsetSet
     */
    public function testThrowsExceptionWhenUsedAsArrayWithoutAKey(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Custom statistics requires a key to be set',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        $this->model[] = 'foobar';
    }

    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $this->model
            ->setNumUsers(100)
            ->setNumBytes(1000)
            ->setNumImages(10000);
        $this->model['some'] = 'value';

        $this->assertSame([
            'numUsers' => 100,
            'numBytes' => 1000,
            'numImages' => 10000,
            'customStats' => ['some' => 'value'],
        ], $this->model->getData());
    }
}
