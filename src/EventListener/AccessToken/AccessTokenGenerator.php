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
        'argumentKey' => 'accessToken',
    ];

    abstract public function generateSignature($data, $privateKey);

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

    public function getArgumentKey() {
        return $this->params['argumentKey'];
    }

    public function setArgumentKey($argumentKey) {
        $this->params['argumentKey'] = $argumentKey;

        return $this;
    }
}