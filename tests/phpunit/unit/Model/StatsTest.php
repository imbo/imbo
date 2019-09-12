<?php
namespace ImboUnitTest\Model;

use Imbo\Model\Stats;
use Imbo\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Model\Stats
 * @group unit
 * @group models
 */
class StatsTest extends TestCase {
    /**
     * @var Stats
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() : void {
        $this->model = new Stats();
    }

    /**
     * Get stats data
     *
     * @return array[]
     */
    public function getStatsData() {
        return [
            [
                0,
                0,
                0
            ],
            [
                1,
                2,
                1349
            ],
            [
                2,
                44,
                100114
            ],
            [
                4,
                14,
                1000
            ],
        ];
    }

    /**
     * @dataProvider getStatsData
     * @covers Imbo\Model\Stats::setNumUsers
     * @covers Imbo\Model\Stats::getNumUsers
     */
    public function testCanSetAndGetNumberOfUsers($users, $images, $bytes) {
        $this->model->setNumUsers($users);
        $this->assertSame($users, $this->model->getNumUsers());
    }

    /**
     * @dataProvider getStatsData
     * @covers Imbo\Model\Stats::setNumImages
     * @covers Imbo\Model\Stats::getNumImages
     */
    public function testCanSetAndGetAmountOfImages($users, $images, $bytes) {
        $this->model->setNumImages($images);
        $this->assertSame($images, $this->model->getNumImages());
    }

    /**
     * @dataProvider getStatsData
     * @covers Imbo\Model\Stats::setNumBytes
     * @covers Imbo\Model\Stats::getNumBytes
     */
    public function testCanSetAndGetAmountOfBytes($users, $images, $bytes) {
        $this->model->setNumBytes($bytes);
        $this->assertSame($bytes, $this->model->getNumBytes());
    }

    /**
     * @covers Imbo\Model\Stats::getCustomStats
     * @covers Imbo\Model\Stats::offsetExists
     * @covers Imbo\Model\Stats::offsetSet
     * @covers Imbo\Model\Stats::offsetGet
     * @covers Imbo\Model\Stats::offsetUnset
     */
    public function testSupportsCustomStats() {
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
     * @covers Imbo\Model\Stats::offsetSet
     */
    public function testThrowsExceptionWhenUsedAsArrayWithoutAKey() {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Custom statistics requires a key to be set',
            500
        ));
        $this->model[] = 'foobar';
    }

    /**
     * @covers Imbo\Model\Stats::getData
     */
    public function testGetData() {
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
