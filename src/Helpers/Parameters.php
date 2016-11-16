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
    /**
     * @param $fields array List of fields to ensure is present in $params
     * @param $params array Associative array with field => value pairs to check for fields being present
     * @return array
     */
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