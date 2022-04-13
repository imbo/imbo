<?php declare(strict_types=1);
namespace Imbo\EventListener\AccessToken;

/**
 * Abstract class for Access Token Generation
 */
abstract class AccessTokenGenerator implements AccessTokenInterface
{
    /**
     * Parameters for the generator
     *
     * @var array<string,mixed>
     */
    protected array $params = [
        'argumentKeys' => ['accessToken'],
    ];

    abstract public function generateSignature(string $argumentKey, string $data, string $privateKey): string;

    /**
     * Class constructor
     *
     * @param array<string,mixed> $params Parameters for the listener
     */
    public function __construct(array $params = null)
    {
        if (null !== $params) {
            $this->params = array_replace_recursive($this->params, $params);
        }
    }

    /**
     * @return array<string>
     */
    public function getArgumentKeys(): array
    {
        return $this->params['argumentKeys'];
    }

    public function addArgumentKey(string $argumentKey): void
    {
        $this->params['argumentKeys'][] = $argumentKey;
    }

    /**
     * @param array<string> $argumentKeys Set the argumentKeys that this generator handles
     */
    public function setArgumentKeys(array $argumentKeys): self
    {
        $this->params['argumentKeys'] = $argumentKeys;
        return $this;
    }
}
