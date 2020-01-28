<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Auth\AccessControl\Adapter\AbstractAdapter;
use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\Model\Groups as GroupsModel;

class StaticAccessControl extends AbstractAdapter implements AdapterInterface {
    public function hasAccess($publicKey, $resource, $user = null) {
        return $publicKey === 'public';
    }

    public function getPrivateKey($publicKey) {
        return 'private';
    }

    public function getGroups(GroupQuery $query = null, GroupsModel $model) {
        return [];
    }

    public function getGroup($groupName) {
        return false;
    }

    public function groupExists($groupName) {
        return false;
    }

    public function userExists($publicKey) {
        return $publicKey === 'public';
    }

    public function publicKeyExists($publicKey) {
        return $publicKey === 'public';
    }

    public function getAccessListForPublicKey($publicKey) {
        return [];
    }

    public function getUsersForResource($publicKey, $resource) {
        return [];
    }

    public function getAccessRule($publicKey, $accessRuleId) {
        return null;
    }
}

return [
    'accessControl' => new StaticAccessControl(),
];
