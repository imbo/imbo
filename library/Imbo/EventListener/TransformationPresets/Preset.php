<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\TransformationPresets;

/**
 * Describes a pre defined set of transformations
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package EventListener\TransformationPresets
 */
class Preset {
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $transformations = array();

    /**
     * @var boolean
     */
    private $argumentsMutable = true;

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getTransformations() {
        return $this->transformations;
    }

    /**
     * @param array $transformations
     */
    public function setTransformations($transformations) {
        $this->transformations = $transformations;
    }

    /**
     * @return boolean
     */
    public function areArgumentsMutable() {
        return (boolean) $this->argumentsMutable;
    }

    /**
     * @param boolean $argumentsMutable
     */
    public function setArgumentsMutable($argumentsMutable) {
        $this->argumentsMutable = $argumentsMutable;
    }
}