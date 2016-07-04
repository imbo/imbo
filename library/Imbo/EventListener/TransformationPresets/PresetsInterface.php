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
 * Interface that describes what an implementation has to support for Imbo to be able to
 * retrieve transformation preset configurations
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package EventListener\TransformationPresets
 */
interface PresetsInterface {
    function hasTransformationPreset($key);
    function getTransformationPreset($key);
}