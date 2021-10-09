<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Model\Groups as GroupsModel;

/**
 * Abstract access control adapter
 */
abstract class AbstractAdapter implements AdapterInterface
{
    abstract public function getGroups(GroupQuery $query, GroupsModel $model): array;
    abstract public function groupExists(string $groupName): bool;
    abstract public function getGroup(string $groupName): ?array;

    public function hasAccess(string $publicKey, string $resource, string $user = null): bool
    {
        $accessList = $this->getAccessListForPublicKey($publicKey) ?: [];

        foreach ($accessList as $acl) {
            // If the group specified has not been defined, throw an exception to help the user
            if (!isset($acl['users'])) {
                throw new InvalidArgumentException('Missing property "users" in access rule', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // If a user is specified, ensure the public key has access to the user
            $userAccess = !$user || $acl['users'] === '*' || in_array($user, $acl['users']);

            if (!$userAccess) {
                continue;
            }

            // Figure out which resources the public key has access to, based on group or
            // explicit definition
            $resources = $acl['resources'] ?? [];
            $group = $acl['group'] ?? false;

            // If we the rule contains a group, get resource from it
            if ($group) {
                $resources = $this->getGroup($group);

                // If the group has not been defined, throw an exception to help debug the problem
                if ($resources === null) {
                    throw new InvalidArgumentException('Group "' . $group . '" is not defined', Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            if (in_array($resource, $resources)) {
                return true;
            }
        }

        return false;
    }

    public function getUsersForResource(string $publicKey, string $resource): array
    {
        $accessList = $this->getAccessListForPublicKey($publicKey);
        $userLists = array_filter(array_map(fn ($acl) => $acl['users'] ?? false, $accessList));
        $users = array_merge(...$userLists);

        if ($this->hasAccess($publicKey, $resource, $publicKey)) {
            $userList[] = $publicKey;
        }

        foreach ($users as $user) {
            if ($this->hasAccess($publicKey, $resource, $user)) {
                $userList[] = $user;
            }
        }

        return array_values(array_unique($userList));
    }
}
