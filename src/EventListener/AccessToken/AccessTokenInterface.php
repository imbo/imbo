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
interface AccessTokenInterface {
    public function generateSignature($data, $privateKey);
    public function getArgumentKey();
}
