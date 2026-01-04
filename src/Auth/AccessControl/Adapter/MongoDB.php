<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\Exception\DatabaseException;
use Imbo\Model\Groups as GroupsModel;
use MongoDB\BSON\ObjectId as MongoId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\Driver\Exception\InvalidArgumentException as MongoDBInvalidArgumentException;
use MongoDB\Model\BSONArray;

class MongoDB extends AbstractAdapter implements MutableAdapterInterface
{
    public const ACL_COLLECTION_NAME       = 'accesscontrol';
    public const ACL_GROUP_COLLECTION_NAME = 'accesscontrolgroup';

    private Collection $aclCollection;
    private Collection $aclGroupCollection;

    /**
     * Create a new MongoDB access control adapter
     *
     * @param string $databaseName The name of the database to use
     * @param string $uri The URI to use when connecting to MongoDB
     * @param array<mixed> $uriOptions Options for the URI, sent to the MongoDB\Client instance
     * @param array<mixed> $driverOptions Additional options for the MongoDB\Client instance
     * @param ?Client $client Pre-configured MongoDB client. When specified $uri, $uriOptions and $driverOptions are ignored
     * @throws DatabaseException
     */
    public function __construct(
        string $databaseName = 'imbo',
        string $uri          = 'mongodb://localhost:27017',
        array $uriOptions    = [],
        array $driverOptions = [],
        ?Client $client      = null,
    ) {
        try {
            $client = $client ?: new Client($uri, $uriOptions, $driverOptions);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to connect to the database', 500, $e);
        }

        $database = $client->selectDatabase($databaseName);
        $this->aclCollection = $database->selectCollection(self::ACL_COLLECTION_NAME);
        $this->aclGroupCollection = $database->selectCollection(self::ACL_GROUP_COLLECTION_NAME);
    }

    public function getGroups(GroupQuery $query, GroupsModel $model): array
    {
        /** @var iterable<array{name:string,resources:BSONArray}> */
        $cursor = $this->aclGroupCollection->find(options: [
            'skip' => ($query->getPage() - 1) * $query->getLimit(),
            'limit' => $query->getLimit(),
        ]);

        $groups = [];

        foreach ($cursor as $group) {
            /** @var array<string> */
            $resources = $group['resources']->getArrayCopy();
            $groups[$group['name']] = $resources;
        }

        $model->setHits($this->aclGroupCollection->countDocuments());

        return $groups;
    }

    public function groupExists(string $groupName): bool
    {
        return null !== $this->aclGroupCollection->findOne([
            'name' => $groupName,
        ]);
    }

    public function getGroup(string $groupName): ?array
    {
        /** @var ?array{resources:BSONArray} */
        $document = $this->aclGroupCollection->findOne([
            'name' => $groupName,
        ]);

        $resources = $document['resources'] ?? null;

        /** @var ?array<string> */
        return $resources?->getArrayCopy();
    }

    public function getPrivateKey(string $publicKey): ?string
    {
        /** @var ?array{privateKey:string} */
        $document = $this->aclCollection->findOne([
            'publicKey' => $publicKey,
        ], [
            'projection' => [
                'privateKey' => 1,
            ],
        ]);

        return $document['privateKey'] ?? null;
    }

    public function addKeyPair(string $publicKey, string $privateKey): true
    {
        try {
            $this->aclCollection->insertOne([
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
                'acl' => [],
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to insert key', 500, $e);
        }

        return true;
    }

    public function deletePublicKey(string $publicKey): bool
    {
        try {
            $result = $this->aclCollection->deleteOne([
                'publicKey' => $publicKey,
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to delete key', 500, $e);
        }

        return (bool) $result->getDeletedCount();
    }

    public function updatePrivateKey(string $publicKey, string $privateKey): bool
    {
        try {
            $result = $this->aclCollection->updateOne([
                'publicKey' => $publicKey,
            ], [
                '$set' => [
                    'privateKey' => $privateKey,
                ],
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to update private key', 500, $e);
        }

        return (bool) $result->getMatchedCount();
    }

    public function getAccessRule(string $publicKey, int|string $accessRuleId): ?array
    {
        $acl = $this->getAccessListForPublicKey($publicKey);

        foreach ($acl as $rule) {
            if ($rule['id'] === $accessRuleId) {
                return $rule;
            }
        }

        return null;
    }

    public function addAccessRule(string $publicKey, array $accessRule): string
    {
        $accessRule['id'] = new MongoId();

        try {
            $this->aclCollection->updateOne([
                'publicKey' => $publicKey,
            ], [
                '$push' => [
                    'acl' => $accessRule,
                ],
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to add access rule', 500, $e);
        }

        return (string) $accessRule['id'];
    }

    public function deleteAccessRule(string $publicKey, string $accessRuleId): bool
    {
        try {
            $result = $this->aclCollection->updateOne([
                'publicKey' => $publicKey,
            ], [
                '$pull' => [
                    'acl' => [
                        'id' => new MongoId($accessRuleId),
                    ],
                ],
            ]);
        } catch (MongoDBInvalidArgumentException $e) {
            throw new DatabaseException(sprintf('Invalid access rule ID: %s', $accessRuleId), 500, $e);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to delete access rule', 500, $e);
        }

        return (bool) $result->getModifiedCount();
    }

    public function addResourceGroup(string $groupName, array $resources = []): true
    {
        try {
            $this->aclGroupCollection->insertOne([
                'name' => $groupName,
                'resources' => $resources,
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to add resource group', 500, $e);
        }

        return true;
    }

    public function updateResourceGroup(string $groupName, array $resources): true
    {
        try {
            $this->aclGroupCollection->updateOne([
                'name' => $groupName,
            ], [
                '$set' => [
                    'resources' => $resources,
                ],
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to update resource group', 500, $e);
        }

        return true;
    }

    public function deleteResourceGroup(string $groupName): bool
    {
        try {
            $result = $this->aclGroupCollection->deleteOne([
                'name' => $groupName,
            ]);

            if ($result->getDeletedCount()) {
                $this->aclCollection->updateMany([
                    'acl.group' => $groupName,
                ], [
                    '$pull' => [
                        'acl' => [
                            'group' => $groupName,
                        ],
                    ],
                ]);
            }
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to delete resource group', 500, $e);
        }

        return (bool) $result->getDeletedCount();
    }

    public function publicKeyExists(string $publicKey): bool
    {
        return null !== $this->aclCollection->findOne([
            'publicKey' => $publicKey,
        ]);
    }

    public function getAccessListForPublicKey(string $publicKey): array
    {
        /** @var ?array{acl:BSONArray} */
        $document = $this->aclCollection->findOne([
            'publicKey' => $publicKey,
        ], [
            'projection' => [
                'acl' => 1,
            ],
        ]);

        /** @var BSONArray<array<string,mixed>> */
        $acls = $document['acl'] ?? [];
        $rules = [];

        foreach ($acls as $rule) {
            if (!$rule['id'] instanceof MongoId) {
                continue;
            }

            $resources = [];
            $users = [];

            if (($rule['resources'] ?? null) instanceof BSONArray) {
                /** @var array<string> */
                $resources = $rule['resources']->getArrayCopy();
            }

            if (($rule['users'] ?? null) instanceof BSONArray) {
                /** @var array<string> */
                $users = $rule['users']->getArrayCopy();
            } elseif ('*' === ($rule['users'] ?? '')) {
                $users = '*';
            }

            $r = [
                'resources' => $resources,
                'users'     => $users,
                'id'        => (string) $rule['id'],
            ];

            if (is_string($rule['group'] ?? null)) {
                $r['group'] = $rule['group'];
            }

            $rules[] = $r;
        }

        return $rules;
    }
}
