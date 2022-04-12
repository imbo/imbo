<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\Auth\AccessControl\Adapter\AbstractAdapter;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\Model\Groups as GroupsModel;

class StaticAccessControl extends AbstractAdapter implements AdapterInterface
{
    public function hasAccess(string $publicKey, string $resource, string $user = null): bool
    {
        return $publicKey === 'public';
    }

    public function getPrivateKey(string $publicKey): ?string
    {
        return 'private';
    }

    public function getGroups(GroupQuery $query, GroupsModel $model): array
    {
        return [];
    }

    public function getGroup(string $groupName): ?array
    {
        return null;
    }

    public function groupExists(string $groupName): bool
    {
        return false;
    }

    public function publicKeyExists(string $publicKey): bool
    {
        return $publicKey === 'public';
    }

    public function getAccessListForPublicKey(string $publicKey): array
    {
        return [];
    }

    public function getUsersForResource(string $publicKey, string $resource): array
    {
        return [];
    }

    public function getAccessRule(string $publicKey, $accessRuleId): ?array
    {
        return null;
    }
}

return [
    'accessControl' => new StaticAccessControl(),
];
