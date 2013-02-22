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
 * Simple model using an associative array for data
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class ArrayModel implements ModelInterface {
    /**
     * Data
     *
     * @var array
     */
    private $data = array();

    /**
     * Set the data
     *
     * @param array $data The data to set
     * @return ArrayModel
     */
    public function setData(array $data) {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the data
     *
     * @return array
     */
    public function getData() {
        return $this->data;
    }
}
