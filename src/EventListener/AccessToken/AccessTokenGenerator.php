<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\AccessToken;

/**
 * Abstract class for Access Token Generation
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package AccessToken
 */
abstract class AccessTokenGenerator implements AccessTokenInterface {
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
    public function __construct(array $params = null) {
        if ($params) {
            $this->params = array_replace_recursive($this->params, $params);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getArgumentKeys() {
        return $this->params['argumentKeys'];
    }

    /**
     * @param $argumentKey string Add an argument key to be handled by this generator
     */
    public function addArgumentKey($argumentKey) {
        $this->params['argumentKeys'][] = $argumentKey;
    }

    /**
     * @param $argumentKeys array<string> Set the argumentKeys that this generator handles
     * @return $this
     */
    public function setArgumentKeys($argumentKeys) {
        $this->params['argumentKeys'] = $argumentKeys;

        return $this;
    }
}