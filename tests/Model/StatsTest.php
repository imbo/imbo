<?php declare(strict_types=1);
namespace Imbo\Model;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Stats::class)]
class StatsTest extends TestCase
{
    private Stats $model;

    public function setUp(): void
    {
        $this->model = new Stats();
    }

    #[DataProvider('getNumUsers')]
    public function testCanSetAndGetNumberOfUsers(int $users): void
    {
        $this->model->setNumUsers($users);
        $this->assertSame($users, $this->model->getNumUsers());
    }

    #[DataProvider('getNumImages')]
    public function testCanSetAndGetAmountOfImages(int $images): void
    {
        $this->model->setNumImages($images);
        $this->assertSame($images, $this->model->getNumImages());
    }

    #[DataProvider('getNumBytes')]
    public function testCanSetAndGetAmountOfBytes(int $bytes): void
    {
        $this->model->setNumBytes($bytes);
        $this->assertSame($bytes, $this->model->getNumBytes());
    }

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

    public function testThrowsExceptionWhenUsedAsArrayWithoutAKey(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Custom statistics requires a key to be set',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        $this->model[] = 'foobar';
    }

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

    /**
     * @return array<array{users:int}>
     */
    public static function getNumUsers(): array
    {
        return [
            ['users' => 0],
            ['users' => 1],
            ['users' => 2],
            ['users' => 4],
        ];
    }

    /**
     * @return array<array{images:int}>
     */
    public static function getNumImages(): array
    {
        return [
            ['images' => 0],
            ['images' => 2],
            ['images' => 44],
            ['images' => 14],
        ];
    }

    /**
     * @return array<array{bytes:int}>
     */
    public static function getNumBytes(): array
    {
        return [
            ['bytes' => 0],
            ['bytes' => 1349],
            ['bytes' => 100114],
            ['bytes' => 1000],
        ];
    }
}
