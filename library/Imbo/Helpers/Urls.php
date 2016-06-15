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
class Urls {
    /**
     * Generate a URL from an array with similar structure as returned from parse_url.
     *
     * @param array $parts An array in the format produced from parse_url
     * @return string
     */
    public static function buildFromParseUrlParts(array $parts) {
        $url = '';

        $url .= isset($parts['scheme']) ? $parts['scheme'] : 'http';
        $url .= '://';

        if (isset($parts['user'])) {
            $url .= $parts['user'];

            if (isset($parts['pass'])) {
                $url .= ':' . $parts['pass'];
            }

            $url .= '@';
        }

        $url .= isset($parts['host']) ? $parts['host'] : '';
        $url .= isset($parts['port']) ? ':' . $parts['port'] : '';
        $url .= isset($parts['path']) ? $parts['path'] : '';
        $url .= isset($parts['query']) ? '?' . $parts['query'] : '';
        $url .= isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $url;
    }
}