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

use stdClass;

/**
 * Object to array helper
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Helpers
 */
class ObjectToArray {
    /**
     * Turn a result set (stdObject) into an array, recursively
     *
     * @param array|stdClass $object
     * @return array
     */
    public static function toArray($object) {
        if (is_array($object)) {
            foreach ($object as $key => $value) {
                if (is_array($value)) {
                    $object[$key] = self::toArray($value);
                }

                if ($value instanceof stdClass) {
                    $object[$key] = self::toArray((array) $value);
                }
            }
        }

        if ($object instanceof stdClass) {
            return self::toArray((array) $object);
        }

        return $object;
    }
}
