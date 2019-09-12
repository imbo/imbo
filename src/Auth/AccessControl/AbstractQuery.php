<?php
namespace Imbo\Auth\AccessControl;

/**
 * Abstract query interface for access control
 *
 * @package Core\Auth\AccessControl
 */
abstract class AbstractQuery {
    /**
     * Limit
     *
     * @var int
     */
    private $limit = 20;

    /**
     * Page
     *
     * @var int
     */
    private $page = 1;

    /**
     * Set or get the limit
     *
     * @param int $limit The limit to set. Skip to get the current value
     * @return self|int
     */
    public function limit($limit = null) {
        if ($limit === null) {
            return $this->limit;
        }

        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * Set or get the page
     *
     * @param int $page The page to set. Skip to get the current value
     * @return self|int
     */
    public function page($page = null) {
        if ($page === null) {
            return $this->page;
        }

        $this->page = (int) $page;

        return $this;
    }
}
