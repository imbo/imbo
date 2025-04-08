<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Model\Groups as GroupsModel;

/**
 * Array-backed access control adapter
 */
class ArrayAdapter extends AbstractAdapter implements AdapterInterface
{
    protected array $accessList = [];
    private array $keys = [];
    private array $groups = [];

    /**
     * Class constructor
     *
     * @param array $accessList Array defining the available public/private keys, along with the
     *                          associated ACL rules for each public key.
     * @param array $groups     Array of group => resources combinations
     */
    public function __construct(array $accessList = [], array $groups = [])
    {
        $this->accessList = $accessList;
        $this->groups = $groups;
        $this->keys = $this->getKeysFromAcl();

        $this->validateAccessList();
    }

    public function getGroups(GroupQuery $query, GroupsModel $model): array
    {
        $model->setHits(count($this->groups));

        $offset = ($query->getPage() - 1) * $query->getLimit();
        return array_slice($this->groups, $offset, $query->getLimit(), true);
    }

    public function groupExists(string $groupName): bool
    {
        return isset($this->groups[$groupName]);
    }

    public function getGroup(string $groupName): ?array
    {
        return isset($this->groups[$groupName]) ? $this->groups[$groupName] : null;
    }

    public function getPrivateKey(string $publicKey): ?string
    {
        if (isset($this->keys[$publicKey])) {
            return $this->keys[$publicKey];
        }

        return null;
    }

    public function publicKeyExists(string $publicKey): bool
    {
        return isset($this->keys[$publicKey]);
    }

    public function getAccessListForPublicKey(string $publicKey): array
    {
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

    public function getAccessRule(string $publicKey, int|string $accessRuleId): ?array
    {
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
    private function getKeysFromAcl()
    {
        $keys = [];
        foreach ($this->accessList as $access) {
            $keys[$access['publicKey']] = $access['privateKey'];
        }

        return $keys;
    }

    /**
     * Validate access list data
     *
     * @throws InvalidArgumentException
     */
    private function validateAccessList()
    {
        // Get all user lists
        $declaredPublicKeys = array_map(function ($acl) {
            return $acl['publicKey'];
        }, $this->accessList);

        $publicKeys = [];
        foreach ($declaredPublicKeys as $key) {
            if (in_array($key, $publicKeys)) {
                throw new InvalidArgumentException('Public key declared twice in config: ' . $key, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $publicKeys[] = $key;
        }
    }
}
