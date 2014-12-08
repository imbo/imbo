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
     * The different private-key modes
     *
     * @var string
     */
    const MODE_READ_ONLY = 'ro';
    const MODE_READ_WRITE = 'rw';

    /**
     * Fetch the private keys for a user
     *
     * @param string $publicKey The public key
     * @param string $mode Optional access control mode (read-only/read+write).
     *                     Will return all keys (read-only AND read+write by default.
     * @return null|array Returns null if the user does not exist, or the private keys otherwise
     */
    function getPrivateKeys($publicKey, $mode = null);

    /**
     * Fetch one or more users
     *
     * @param UserLookup\Query $query A query object used to filter the users returned
     * @return string[] Returns a list of users
     */
    function getUsers(UserLookup\Query $query = null);

    /**
     * Return whether the public key given exists or not
     *
     * @param  string $publicKey The public key to check
     * @return boolean
     */
    function publicKeyExists($publicKey);

    /**
     * Return whether the user given exists or not
     *
     * @param  string $user The user to check
     * @return boolean
     */
    function userExists($user);
}
