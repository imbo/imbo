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
 * Store users as an array
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Core\Auth
 */
class ArrayStorage implements UserLookupInterface {
    /**
     * Users
     *
     * @var array
     */
    private $users = array();

    /**
     * Class constructor
     *
     * @param array $users The users
     */
    public function __construct(array $users) {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey($publicKey) {
        return isset($this->users[$publicKey]) ? $this->users[$publicKey] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicKeys(UserLookup\Query $query = null) {
        if (!$query) {
            $query = new UserLookup\Query();
        }

        return array_slice(array_keys($this->users), $query->offset() ?: 0, $query->limit());
    }
}
