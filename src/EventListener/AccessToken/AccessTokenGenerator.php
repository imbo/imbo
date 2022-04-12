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
     * @var array
     */
    protected $params = [
        'argumentKeys' => ['accessToken'],
    ];

    /**
     * {@inheritdoc}
     */
    abstract public function generateSignature($argumentKey, $data, $privateKey);

    /**
     * Class constructor
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(array $params = null)
    {
        if ($params) {
            $this->params = array_replace_recursive($this->params, $params);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getArgumentKeys()
    {
        return $this->params['argumentKeys'];
    }

    /**
     * @param string $argumentKey Add an argument key to be handled by this generator
     */
    public function addArgumentKey($argumentKey)
    {
        $this->params['argumentKeys'][] = $argumentKey;
    }

    /**
     * @param string[] $argumentKeys Set the argumentKeys that this generator handles
     * @return self
     */
    public function setArgumentKeys($argumentKeys)
    {
        $this->params['argumentKeys'] = $argumentKeys;

        return $this;
    }
}
