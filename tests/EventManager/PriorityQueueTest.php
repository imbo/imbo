<?php declare(strict_types=1);
namespace Imbo\EventManager;

use PHPUnit\Framework\TestCase;
use SplPriorityQueue;

/**
 * @coversDefaultClass Imbo\EventManager\PriorityQueue
 */
class PriorityQueueTest extends TestCase
{
    /**
     * @covers ::insert
     */
    public function testUsesAPredictableOrder(): void
    {
        /** @var SplPriorityQueue<int,string> */
        $queue = new PriorityQueue();

        for ($i = 0; $i < 10; $i++) {
            $queue->insert((string) $i, 10);
        }

        $this->expectOutputString('0123456789');

        foreach ($queue as $value) {
            echo $value;
        }
    }
}
