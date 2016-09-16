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
    private $data = [];

    /**
     * Title of the model, used in representations
     *
     * @var string
     */
    private $title;

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
     * {@inheritdoc}
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Set the title of the model
     *
     * @param string $title The title of the model, for instance "Statistics"
     * @return ArrayModel
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title of the model
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }
}
