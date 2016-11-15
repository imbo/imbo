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
* Helper class for useful functions for building/manipulating URLs
*
* @author Mats Lindh <mats@lindh.no>
* @package Core\Helpers
*/
class Parameters {
    public static function getEmptyOrMissingParamFields($fields, $params) {
        $missing = [];

        foreach ($fields as $field) {
            if (empty($params[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}