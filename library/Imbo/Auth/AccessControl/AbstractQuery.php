<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Auth\AccessControl;

/**
 * Abstract query interface for access control
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
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
