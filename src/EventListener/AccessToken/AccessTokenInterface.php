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
 * Interface for Access Token Generation
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package AccessToken
 */
interface AccessTokenInterface {
    /**
     * @param $argumentKey string The URL argument used for key comparison
     * @param $data string The data to be signed
     * @param $privateKey string The private key used to sign the data
     * @return string The generated signature from the parameters given
     */
    public function generateSignature($argumentKey, $data, $privateKey);

    /**
     * @return array<string> The defined argument keys handled by this generator
     */
    public function getArgumentKeys();
}
