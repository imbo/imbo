<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Http;

/**
 * Content negotiation
 *
 * @package Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
