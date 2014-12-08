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
    private $users = [];

    /**
     * Read-only private keys
     *
     * @var array
     */
    private $roPrivateKeys = [];

    /**
     * Read+write private keys
     *
     * @var array
     */
    private $rwPrivateKeys = [];

    /**
     * Class constructor
     *
     * @param array $users The users
     */
    public function __construct(array $users) {
        $this->users = array_keys($users);

        foreach ($users as $publicKey => $privateKeys) {
            $this->setPrivateKeysForUser($publicKey, $privateKeys);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKeys($publicKey, $mode = null) {
        $roKeys = isset($this->roPrivateKeys[$publicKey]) ? $this->roPrivateKeys[$publicKey] : [];
        $rwKeys = isset($this->rwPrivateKeys[$publicKey]) ? $this->rwPrivateKeys[$publicKey] : [];
        $keys   = [];

        if ($mode === null) {
            $keys = array_unique(array_merge($roKeys, $rwKeys));
        } else {
            $keys = $mode === UserLookupInterface::MODE_READ_ONLY ? $roKeys : $rwKeys;
        }

        return empty($keys) ? null : $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsers(UserLookup\Query $query = null) {
        if ($query === null) {
            $query = new UserLookup\Query();
        }

        return array_slice($this->users, $query->offset() ?: 0, $query->limit());
    }

    /**
     * {@inheritdoc}
     */
    public function publicKeyExists($publicKey) {
        return in_array($publicKey, $this->users);
    }

    /**
     * {@inheritdoc}
     */
    public function userExists($user) {
        return in_array($user, $this->users);
    }

    /**
     * Set private keys for a given public key
     *
     * @param string $publicKey Public key to assign private keys to
     * @param string|array $keys One or more private keys
     */
    protected function setPrivateKeysForUser($publicKey, $keys) {
        $roKeys = [];
        $rwKeys = [];

        if (is_string($keys)) {
            // Only one key specified, treat it as a read+write key
            $roKeys[] = $keys;
            $rwKeys[] = $keys;
        } else if (is_array($keys)) {
            // Individual read-only/read+write keys specified
            $rwKeys = isset($keys['rw']) ? $keys['rw'] : [];
            $roKeys = isset($keys['ro']) ? $keys['ro'] : [];
        }

        $this->roPrivateKeys[$publicKey] = (array) $roKeys;
        $this->rwPrivateKeys[$publicKey] = (array) $rwKeys;
    }
}
