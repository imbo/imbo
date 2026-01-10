<?php declare(strict_types=1);

namespace Imbo\EventListener\AccessToken;

/**
 * Interface for Access Token Generation.
 */
interface AccessTokenInterface
{
    /**
     * @param string $argumentKey The URL argument used for key comparison
     * @param string $data        The data to be signed
     * @param string $privateKey  The private key used to sign the data
     *
     * @return string The generated signature from the parameters given
     */
    public function generateSignature(string $argumentKey, string $data, string $privateKey): string;

    /**
     * @return array<string> The defined argument keys handled by this generator
     */
    public function getArgumentKeys(): array;
}
