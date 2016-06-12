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

use Guzzle\Http\QueryAggregator\QueryAggregatorInterface,
    Guzzle\Http\QueryString;
/**
 * Helper class for serializing query string arrays as foo[]
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Core\Helpers
 */
class QueryStringArrayConverter implements QueryAggregatorInterface {
    public function aggregate($key, $value, QueryString $query) {
        return [
            $query->encodeValue($key . "[]") => array_map([$query, 'encodeValue'], $value),
        ];
    }
}
