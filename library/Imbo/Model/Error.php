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
    private $httpCode;

    /**
     * Error message from Imbo
     *
     * @var string
     */
    private $errorMessage;

    /**
     * Current date
     *
     * @var DateTime
     */
    private $date;

    /**
     * Internal Imbo error code
     *
     * @var int
     */
    private $imboErrorCode;

    /**
     * Optional image identifier
     *
     * @var string
     */
    private $imageIdentifier;

    /**
     * Set the HTTP code
     *
     * @param int $code The code to set
     * @return Error
     */
    public function setHttpCode($code) {
        $this->httpCode = (int) $code;

        return $this;
    }

    /**
     * Get the HTTP code
     *
     * @return int
     */
    public function getHttpCode() {
        return $this->httpCode;
    }

    /**
     * Set the error message
     *
     * @param string $message The message to set
     * @return Error
     */
    public function setErrorMessage($message) {
        $this->errorMessage = $message;

        return $this;
    }

    /**
     * Get the error message
     *
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * Set the date
     *
     * @param DateTime $date The DateTime instance to set
     * @return Error
     */
    public function setDate(DateTime $date) {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the date
     *
     * @return DateTime
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set the imbo error code
     *
     * @param int $code The code to set
     * @return Error
     */
    public function setImboErrorCode($code) {
        $this->imboErrorCode = (int) $code;

        return $this;
    }

    /**
     * Get the imbo error code
     *
     * @return int
     */
    public function getImboErrorCode() {
        return $this->imboErrorCode;
    }

    /**
     * Set the image identifier
     *
     * @param string $imageIdentifier The image identifier to set
     * @return Error
     */
    public function setImageIdentifier($imageIdentifier) {
        $this->imageIdentifier = $imageIdentifier;

        return $this;
    }

    /**
     * Get the image identifier
     *
     * @return string
     */
    public function getImageIdentifier() {
        return $this->imageIdentifier;
    }
}
