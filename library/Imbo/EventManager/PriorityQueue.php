<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventManager;

use SplPriorityQueue;

/**
 * A priority queue used for event listeners
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Manager
 */
class PriorityQueue extends SplPriorityQueue {
    /**
     * Queue order counter
     *
     * @var int
     */
    private $queueOrder = PHP_INT_MAX;

    /**
     * Add an entry to the queue
     *
     * @param mixed $datum The entry to add
     * @param int $priority The priority of the entry in the queue
     */
    public function insert($datum, $priority) {
        if (is_int($priority)) {
            $priority = [$priority, $this->queueOrder--];
        }

        parent::insert($datum, $priority);
    }
}
