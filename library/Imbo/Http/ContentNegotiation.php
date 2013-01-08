<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http;

/**
 * Content negotiation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class ContentNegotiation {
    /**
     * Pick the best match from a set of mime types matched against acceptable types
     *
     * @param array $mimeTypes The mime types to find the best match from
     * @param array $acceptable Acceptable types to match against
     * @return boolean|string Returns false if none of the $mimetypes are acceptable or the best
     *                        match on success
     */
    public function bestMatch(array $mimeTypes, array $acceptable) {
        $maxQ = 0;
        $match = false;

        foreach ($mimeTypes as $mime) {
            if (($q = $this->isAcceptable($mime, $acceptable)) && ($q > $maxQ)) {
                $maxQ = $q;
                $match = $mime;
            }
        }

        return $match;
    }

    /**
     * See if a mime type is accepted
     *
     * @param string $mimeType The mime type to check, for instance "image/png"
     * @param array $acceptable An array of acceptable content types as keys and the quality as
     *                          value
     * @return boolean|double Returns the quality of the mime type if it is accepted, or false
     *                        otherwise
     */
    public function isAcceptable($mimeType, array $acceptable) {
        foreach ($acceptable as $type => $q) {
            $pattern = '#^' . str_replace('*', '.*', $type) . '#';

            if (preg_match($pattern, $mimeType)) {
                return $q;
            }
        }

        return false;
    }
}
