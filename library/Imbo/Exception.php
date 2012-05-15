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
 * @package Interfaces
 * @subpackage Exceptions
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo;

/**
 * Base exception interface
 *
 * @package Interfaces
 * @subpackage Exceptions
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
interface Exception {
    /**#@+
     * Internal error codes
     *
     * @var int
     */
    const ERR_UNSPECIFIED = 0;

    // Auth errors
    const AUTH_UNKNOWN_PUBLIC_KEY = 100;
    const AUTH_MISSING_PARAM      = 101;
    const AUTH_INVALID_TIMESTAMP  = 102;
    const AUTH_SIGNATURE_MISMATCH = 103;
    const AUTH_TIMESTAMP_EXPIRED  = 104;

    // Image resource errors
    const IMAGE_ALREADY_EXISTS       = 200;
    const IMAGE_NO_IMAGE_ATTACHED    = 201;
    const IMAGE_HASH_MISMATCH        = 202;
    const IMAGE_UNSUPPORTED_MIMETYPE = 203;
    const IMAGE_BROKEN_IMAGE         = 204;
    /**#@-*/

    /**
     * Set an internal Imbo error code
     *
     * @param int $code One of the constants defined in this class
     * @return Imbo\Exception
     */
    function setImboErrorCode($code);

    /**
     * Get the internal Imbo error code
     *
     * @return int
     */
    function getImboErrorCode();
}
