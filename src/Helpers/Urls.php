<?php declare(strict_types=1);

namespace Imbo\Helpers;

class Urls
{
    /**
     * Generate a URL from an array with similar structure as returned from parse_url.
     *
     * @param array $parts An array in the format produced from parse_url
     */
    public static function buildFromParseUrlParts(array $parts): string
    {
        $url = '';

        $url .= isset($parts['scheme']) ? $parts['scheme'] : 'http';
        $url .= '://';

        if (isset($parts['user'])) {
            $url .= $parts['user'];

            if (isset($parts['pass'])) {
                $url .= ':'.$parts['pass'];
            }

            $url .= '@';
        }

        $url .= isset($parts['host']) ? $parts['host'] : '';
        $url .= isset($parts['port']) ? ':'.$parts['port'] : '';
        $url .= isset($parts['path']) ? $parts['path'] : '';
        $url .= isset($parts['query']) ? '?'.$parts['query'] : '';
        $url .= isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $url;
    }
}
