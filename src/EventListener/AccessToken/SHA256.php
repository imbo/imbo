<?php declare(strict_types=1);

namespace Imbo\EventListener\AccessToken;

class SHA256 extends AccessTokenGenerator
{
    public function generateSignature(string $argumentKey, string $data, string $privateKey): string
    {
        return hash_hmac('sha256', $data, $privateKey);
    }
}
