<?php declare(strict_types=1);

namespace Imbo\Exception;

use Imbo\Exception;
use RuntimeException as BaseRuntimeException;

class RuntimeException extends BaseRuntimeException implements Exception
{
    private ?int $imboCode = null;

    public function setImboErrorCode(int $code): self
    {
        $this->imboCode = $code;

        return $this;
    }

    public function getImboErrorCode(): ?int
    {
        return $this->imboCode;
    }
}
