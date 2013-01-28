<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Model;

/**
 * Metadata model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Metadata implements ModelInterface {
    /**
     * Metadata
     *
     * @var array
     */
    private $data = array();

    /**
     * Set metadata
     *
     * @param array $data The metadata
     * @return Metadata
     */
    public function setData(array $data) {
        $this->data = $data;

        return $this;
    }

    /**
     * Get metadata
     *
     * @return array
     */
    public function getData() {
        return $this->data;
    }
}
