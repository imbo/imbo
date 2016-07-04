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
 * Load a list of transformation presets from an array.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package EventListener\TransformationPresets
 */
class PresetsArrayAdapter implements PresetsInterface {
    private $presets = array();

    public function __construct(array $presets) {
        foreach ($presets as $name => $params) {
            $preset = new Preset();
            $preset->setName($name);
            $preset->setTransformations($params);
            $preset->isArgumentsMutable(true);
            $this->presets[$name] = $preset;
        }
    }

    public function hasTransformationPreset($key) {
        return isset($this->presets[$key]);
    }

    public function getTransformationPreset($key) {
        if ($this->hasTransformationPreset($key)) {
            return $this->presets[$key];
        }

        return null;
    }
}