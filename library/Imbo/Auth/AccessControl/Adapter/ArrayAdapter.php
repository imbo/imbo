<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Exception\InvalidArgumentException,
    Imbo\Auth\AccessControl\GroupQuery;

/**
 * Array-backed access control adapter
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl\Adapter
 */
class ArrayAdapter extends AbstractAdapter implements AdapterInterface {
    /**
     * Access control definitions
     *
     * @var array
     */
    protected $accessList = [];

    /**
     * Public => private key pairs
     *
     * @var array
     */
    private $keys = [];

    /**
     * Resource groups
     *
     * @var array
     */
    private $groups = [];

    /**
     * Class constructor
     *
     * @param array $accessList Array defining the available public/private keys, along with the
     *                          associated ACL rules for each public key.
     * @param array $groups     Array of group => resources combinations
     */
    public function __construct(array $accessList = [], $groups = []) {
        $this->accessList = $accessList;
        $this->groups = $groups;
        $this->keys = $this->getKeysFromAcl();
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersForResource($publicKey, $resource) {
        if (!$publicKey || !$resource) {
            return [];
        }

        $accessList = $this->getAccessListForPublicKey($publicKey);

        // Get all user lists
        $userLists = array_filter(array_map(function($acl) {
            return isset($acl['users']) ? $acl['users'] : false;
        }, $accessList));

        // Merge user lists
        $users = call_user_func_array('array_merge', $userLists);

        // Check if public key has access to user with same name
        if ($this->hasAccess($publicKey, $resource, $publicKey)) {
            $userList[] = $publicKey;
        }

        // Check for each user specified in acls
        foreach ($users as $user) {
            if ($this->hasAccess($publicKey, $resource, $user)) {
                $userList[] = $user;
            }
        }

        return $userList;
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
                // If the group specified has not been defined, throw an exception to help the user
                if (!isset($acl['users'])) {
                    throw new InvalidArgumentException('Missing property "users" in access rule');
                }

                // If a user is specified, ensure the public key has access to the user
                $userAccess = !$user || $acl['users'] === '*' || in_array($user, $acl['users']);
                if (!$userAccess) {
                    continue;
                }

                // Figure out which resources the public key has access to, based on group or
                // explicit definition
                $resources = isset($acl['resources']) ? $acl['resources'] : [];
                $group = isset($acl['group']) ? $acl['group'] : false;

                // If the group specified has not been defined, throw an exception to help the user
                if ($group && !isset($this->groups[$group])) {
                    throw new InvalidArgumentException('Group "' . $group . '" is not defined');
                }

                // If a group is specified, fetch resources belonging to that group
                if ($group) {
                    $resources = $this->groups[$group];
                }

                if (in_array($resource, $resources)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups(GroupQuery $query = null) {
        if ($query === null) {
            $query = new GroupQuery();
        }

        return array_slice($this->groups, $query->offset() ?: 0, $query->limit(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup($groupName) {
        return isset($this->groups[$groupName]) ? $this->groups[$groupName] : false;
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
     * {@inheritdoc}
     */
    public function publicKeyExists($publicKey) {
        return isset($this->keys[$publicKey]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessListForPublicKey($publicKey) {
        $accessList = [];

        foreach ($this->accessList as $i => $access) {
            if ($access['publicKey'] !== $publicKey) {
                continue;
            }

            foreach ($access['acl'] as $index => $rule) {
                // We can't modify or delete rules as this is an immutable adapter, but we still
                // generate an ID for the rule to provide a consistent data structure
                $accessList[] = array_merge(['id' => ($index + 1)], $rule);
            }
        }

        return $accessList;
    }

    /**
     * {@inheritdoc}
     */
    function getAccessRule($publicKey, $accessRuleId) {
        foreach ($this->getAccessListForPublicKey($publicKey) as $rule) {
            if ($rule['id'] == $accessRuleId) {
                return $rule;
            }
        }

        return null;
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
