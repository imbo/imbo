<?php
namespace ImboUnitTest\EventManager;

use Imbo\EventManager\PriorityQueue;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventManager\PriorityQueue
 */
class PriorityQueueTest extends TestCase {
    /**
     * @var PriorityQueue
     */
    private $queue;

    public function testUsesAPredictableOrder() : void {
        $queue = new PriorityQueue();

        for ($i = 0; $i < 10; $i++) {
            $queue->insert($i, 10);
        }

        $this->expectOutputString('0123456789');

        foreach ($queue as $value) {
            echo $value;
        }
    }
}
