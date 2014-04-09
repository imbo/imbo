<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Auth;

use Iterator;

/**
 * Imbo user lookup interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Core\Auth
 */
interface UserLookupInterface extends Iterator {
    /**
     * Fetch the private key of a user
     *
     * @param string $publicKey The public key
     * @return null|string Returns null if the user does not exist, or the private key otherwise
     */
    function getPrivateKey($publicKey);
}
