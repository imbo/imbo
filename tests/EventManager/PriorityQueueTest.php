<?php declare(strict_types=1);

namespace Imbo\EventManager;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PriorityQueue::class)]
class PriorityQueueTest extends TestCase
{
    public function testUsesAPredictableOrder(): void
    {
        $queue = new PriorityQueue();

        for ($i = 0; $i < 10; ++$i) {
            $queue->insert((string) $i, 10);
        }

        $this->expectOutputString('0123456789');

        foreach ($queue as $value) {
            echo $value;
        }
    }
}
