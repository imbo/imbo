<?php
namespace Imbo\Model;

/**
 * Access rules model
 *
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
