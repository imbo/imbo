<?php
namespace Imbo\Exception;

use Imbo\Exception,
    InvalidArgumentException as BaseInvalidArgumentException;

/**
 * Invalid argument exception
 *
 * @package Exceptions
 */
class InvalidArgumentException extends BaseInvalidArgumentException implements Exception {
    /**
     * Internal Imbo error code injected into the error output
     *
     * @var int
     */
    private $imboCode;

    /**
     * {@inheritdoc}
     */
    public function setImboErrorCode($code) {
        $this->imboCode = (int) $code;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getImboErrorCode() {
        return $this->imboCode;
    }
}
