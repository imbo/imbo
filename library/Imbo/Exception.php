<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo;

/**
 * Base exception interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Exceptions
 */
interface Exception {
    /**#@+
     * Internal error codes
     *
     * @var int
     */
    const ERR_UNSPECIFIED = 0;

    // Auth errors
    const AUTH_MISSING_PARAM      = 101;
    const AUTH_INVALID_TIMESTAMP  = 102;
    const AUTH_SIGNATURE_MISMATCH = 103;
    const AUTH_TIMESTAMP_EXPIRED  = 104;

    // Image resource errors
    const IMAGE_ALREADY_EXISTS               = 200;
    const IMAGE_NO_IMAGE_ATTACHED            = 201;
    const IMAGE_HASH_MISMATCH                = 202;
    const IMAGE_UNSUPPORTED_MIMETYPE         = 203;
    const IMAGE_BROKEN_IMAGE                 = 204;
    const IMAGE_INVALID_IMAGE                = 205;
    const IMAGE_IDENTIFIER_GENERATION_FAILED = 206;
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
