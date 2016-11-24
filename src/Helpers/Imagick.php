<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Helpers;

/**
 * Helper class for Imagick
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Core\Helpers
 */
class Imagick {
    public static function getInstalledVersion() {
        $params = explode(' ', \Imagick::getVersion()['versionString']);

        if (count($params) > 2) {
            return $params[1];
        }

        return null;
    }
}