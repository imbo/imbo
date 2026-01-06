<?php declare(strict_types=1);
namespace Imbo\EventManager;

use SplPriorityQueue;

class PriorityQueue extends SplPriorityQueue
{
    private int $queueOrder = PHP_INT_MAX;

    /**
     * Add an entry to the queue
     *
     * @param mixed $datum The entry to add
     * @param int $priority The priority of the entry in the queue
     * @return true
     */
    public function insert($datum, $priority): true
    {
        if (is_int($priority)) {
            $priority = [$priority, $this->queueOrder--];
        }

        return parent::insert($datum, $priority);
    }
}
