<?php
namespace Imbo\Exception;

use Imbo\Exception;
use RuntimeException as BaseRuntimeException;

/**
 * Runtime exception
 */
class RuntimeException extends BaseRuntimeException implements Exception {
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
