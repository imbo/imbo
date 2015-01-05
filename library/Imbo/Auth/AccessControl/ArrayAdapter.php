<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Auth\AccessControl;

use Imbo\Auth\AccessControl\AccessControlAdapter as Adapter;
use Imbo\Exception\InvalidArgumentException;

/**
 * Array-backed access control adapter
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl
 */
class ArrayAdapter extends Adapter implements AccessControlInterface {
    /**
     * Access control definitions
     *
     * @var array
     */
    private $accessList = [];

    /**
     * Public => private key pairs
     *
     * @var array
     */
    private $keys = [];

    /**
     * Users
     *
     * @var array
     */
    private $users = [];

    /**
     * Class constructor
     *
     * @param array $accessList
     */
    public function __construct(array $accessList = []) {
        $this->accessList = $accessList;
        $this->users = array_unique($this->getUsersFromAcl());
        $this->keys = $this->getKeysFromAcl();
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccess($publicKey, $resource, $user = null) {
        foreach ($this->accessList as $access) {
            if ($access['publicKey'] !== $publicKey) {
                continue;
            }

            foreach ($access['acl'] as $acl) {
                $userAccess = !$user || in_array($user, $acl['users']);

                if ($userAccess && in_array($resource, $acl['resources'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsers(UserQuery $query = null) {
        if ($query === null) {
            $query = new UserQuery();
        }

        return array_slice($this->users, $query->offset() ?: 0, $query->limit());
    }

    /**
     * {@inheritdoc}
     */
    public function userExists($user) {
        return in_array($user, $this->users);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey($publicKey) {
        if (isset($this->keys[$publicKey])) {
            return $this->keys[$publicKey];
        }

        return null;
    }

    /**
     * For compatibility reasons, where the configuration for Imbo has a set of
     * 'public key' => 'private key' pairs - this method converts that config
     * to an AccessControl-compatible format. Public key will equal the user.
     *
     * @param array $authDetails
     */
    public function setAccessListFromAuth(array $authDetails) {
        foreach ($authDetails as $publicKey => $privateKey) {
            if (!in_array($publicKey, $this->users)) {
                $this->users[] = $publicKey;
            }

            if (is_array($privateKey)) {
                throw new InvalidArgumentException('A public key can only have a single private key (as of 2.0.0)');
            }

            $this->accessList[] = [
                'publicKey'  => $publicKey,
                'privateKey' => $privateKey,
                'acl' => [[
                    'resources' => $this->getReadWriteResources(),
                    'users' => [$publicKey]
                ]]
            ];

            $this->keys[$publicKey] = $privateKey;
        }
    }

    /**
     * Get an array of users defined in the ACL
     *
     * @return array
     */
    private function getUsersFromAcl() {
        $users = [];
        foreach ($this->accessList as $access) {
            foreach ($access['acl'] as $acl) {
                $users = array_merge($users, $acl['users']);
            }
        }

        return $users;
    }

    /**
     * Get an array of public => private key pairs defined in the ACL
     *
     * @return array
     */
    private function getKeysFromAcl() {
        $keys = [];
        foreach ($this->accessList as $access) {
            $keys[$access['publicKey']] = $access['privateKey'];
        }

        return $keys;
    }
}
