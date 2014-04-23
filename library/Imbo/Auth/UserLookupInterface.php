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

/**
 * Imbo user lookup interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Core\Auth
 */
interface UserLookupInterface {
    /**
     * Fetch the private key of a user
     *
     * @param string $publicKey The public key
     * @return null|string Returns null if the user does not exist, or the private key otherwise
     */
    function getPrivateKey($publicKey);

    /**
     * Fetch one or more public keys
     *
     * @param UserLookup\Query $query A query object used to filter the public keys returned
     * @return string[] Returns a list of public keys
     */
    function getPublicKeys(UserLookup\Query $query = null);
}
