<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Imbo\Auth\AccessControl\AccessControlInterface,
    Imbo\Auth\AccessControl\AccessControlAdapter,
    Imbo\Auth\AccessControl\UserQuery;

/**
 * Use a custom user lookup implementation
 */
class StaticAccessControl extends AccessControlAdapter implements AccessControlInterface {
    public function hasAccess($publicKey, $resource, $user = null) {
        return $publicKey === 'public';
    }

    public function getPrivateKey($publicKey) {
        return 'private';
    }

    public function getUsers(UserQuery $query = null) {
        return ['public'];
    }

    public function userExists($publicKey) {
        return $publicKey === 'public';
    }
}

return [
    'accessControl' => new StaticAccessControl(),
];
