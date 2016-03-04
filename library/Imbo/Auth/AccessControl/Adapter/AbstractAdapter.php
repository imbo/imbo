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

use Imbo\Auth\AccessControl\Adapter\AdapterInterface,
    Imbo\Auth\AccessControl\GroupQuery,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Model\Groups as GroupsModel;

/**
 * Abstract access control adapter
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl
 */
abstract class AbstractAdapter implements AdapterInterface {
    /**
     * {@inheritdoc}
     */
    abstract public function getGroups(GroupQuery $query = null, GroupsModel $model);

    /**
     * {@inheritdoc}
     */
    abstract public function groupExists($groupName);

    /**
     * {@inheritdoc}
     */
    abstract public function getGroup($groupName);

    /**
     * {@inheritdoc}
     */
    public function hasAccess($publicKey, $resource, $user = null) {
        $accessList = $this->getAccessListForPublicKey($publicKey) ?: [];

        foreach ($accessList as $acl) {
            // If the group specified has not been defined, throw an exception to help the user
            if (!isset($acl['users'])) {
                throw new InvalidArgumentException('Missing property "users" in access rule', 500);
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

            // If we the rule contains a group, get resource from it
            if ($group) {
                $resources = $this->getGroup($group);

                // If the group has not been defined, throw an exception to help debug the problem
                if ($resources === false) {
                    throw new InvalidArgumentException('Group "' . $group . '" is not defined', 500);
                }
            }

            if (in_array($resource, $resources)) {
                return true;
            }
        }

        return false;
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
}
