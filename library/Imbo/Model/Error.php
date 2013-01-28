<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Model;

use DateTime;

/**
 * Error model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Error implements ModelInterface {
    /**
     * HTTP code
     *
     * @var int
     */
    public $httpCode;

    /**
     * Error message from Imbo
     *
     * @var string
     */
    public $errorMessage;

    /**
     * Current date
     *
     * @var DateTime
     */
    public $date;

    /**
     * Internal Imbo error code
     *
     * @var int
     */
    public $imboErrorCode;

    /**
     * Optional image identifier
     *
     * @var string
     */
    public $imageIdentifier;
}
