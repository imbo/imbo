<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventManager;

use Imbo\EventManager\PriorityQueue;

/**
 * @covers Imbo\EventManager\PriorityQueue
 * @group unit
 * @group eventmanager
 */
class PriorityQueueTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var PriorityQueue
     */
    private $queue;

    public function testUsesAPredictableOrder() {
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
