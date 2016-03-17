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
 * Access rules model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class AccessRules implements ModelInterface {
    /**
     * List of rules
     *
     * @var array[]
     */
    private $rules = [];

    /**
     * Set the rules
     *
     * @param array[]
     * @return self
     */
    public function setRules(array $rules) {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Get the rules
     *
     * @return array[]
     */
    public function getRules() {
        return $this->rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return $this->getRules();
    }
}
