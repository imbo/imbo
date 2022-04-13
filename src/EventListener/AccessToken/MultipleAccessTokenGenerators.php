<?php declare(strict_types=1);
namespace Imbo\EventListener\AccessToken;

use Imbo\Exception\RuntimeException;

class MultipleAccessTokenGenerators extends AccessTokenGenerator
{
    /**
     * The params array for this generator allows you to define a set of access token signature generators to be used
     * for different url parameters if present. Each will be tried in the sequence defined.
     *
     * 'generators' => [
     *     'accessToken' => new SHA256(),
     *     'dummy' => new Dummy(),
     * ]
     *
     * .. will first try the SHA256() generator if the 'accessToken' parameter is present in the URL, before trying
     * the Dummy generator if the first authentication attempt fails. This allows you to introduce new access token
     * validation schemes while keeping backwards compatible signature algorithms valid.
     *
     * @param array $params Parameters to the MultipleAccessTokenGenerators.
     */
    public function __construct(array $params = [])
    {
        if (!isset($params['generators']) || !is_array($params['generators'])) {
            $params['generators'] = [];
        } else {
            foreach ($params['generators'] as $generator) {
                if (!$generator instanceof AccessTokenInterface) {
                    throw new RuntimeException('AccessTokenGenerators must implement AccessTokenInterface');
                }
            }
        }

        parent::__construct($params);
    }

    public function generateSignature(string $argumentKey, string $data, string $privateKey): string
    {
        return $this->params['generators'][$argumentKey]->generateSignature($argumentKey, $data, $privateKey);
    }

    /**
     * @return array<string>
     */
    public function getArgumentKeys(): array
    {
        return array_keys($this->params['generators']);
    }
}
