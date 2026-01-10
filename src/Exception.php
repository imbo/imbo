<?php declare(strict_types=1);

namespace Imbo;

use Throwable;

interface Exception extends Throwable
{
    public const ERR_UNSPECIFIED = 0;

    public const AUTH_MISSING_PARAM = 101;
    public const AUTH_INVALID_TIMESTAMP = 102;
    public const AUTH_SIGNATURE_MISMATCH = 103;
    public const AUTH_TIMESTAMP_EXPIRED = 104;

    public const IMAGE_ALREADY_EXISTS = 200;
    public const IMAGE_NO_IMAGE_ATTACHED = 201;
    public const IMAGE_HASH_MISMATCH = 202;
    public const IMAGE_UNSUPPORTED_MIMETYPE = 203;
    public const IMAGE_BROKEN_IMAGE = 204;
    public const IMAGE_INVALID_IMAGE = 205;
    public const IMAGE_IDENTIFIER_GENERATION_FAILED = 206;

    public function setImboErrorCode(int $code): self;

    public function getImboErrorCode(): ?int;
}
