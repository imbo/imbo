<?php declare(strict_types=1);
namespace Imbo\EventManager;

use SplPriorityQueue;

class PriorityQueue extends SplPriorityQueue
{
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
    public function insert($datum, $priority)
    {
        if (is_int($priority)) {
            $priority = [$priority, $this->queueOrder--];
        }

        parent::insert($datum, $priority);
    }
}
