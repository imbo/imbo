<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Imbo\Auth\UserLookupInterface,
    Imbo\Auth\UserLookup\Query;

/**
 * Use a custom user lookup implementation
 */
class StaticUserLookup implements UserLookupInterface {
    public function getPrivateKeys($publicKey, $mode = null) {
        return ['private'];
    }

    public function getUsers(Query $query = null) {
        return ['public'];
    }

    public function publicKeyExists($publicKey) {
        return $publicKey === 'public';
    }

    public function userExists($publicKey) {
        return $publicKey === 'public';
    }
}

return [
    'auth' => new StaticUserLookup(),
];
