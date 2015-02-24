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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Core\Auth\AccessControl
 */
abstract class AbstractQuery {
    /**
     * Limit
     *
     * @var int
     */
    private $limit;

    /**
     * Offset
     *
     * @var int
     */
    private $offset;

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
     * Set or get the offset
     *
     * @param int $offset The offset to set. Skip to get the current value
     * @return self|int
     */
    public function offset($offset = null) {
        if ($offset === null) {
            return $this->offset;
        }

        $this->offset = (int) $offset;

        return $this;
    }
}
