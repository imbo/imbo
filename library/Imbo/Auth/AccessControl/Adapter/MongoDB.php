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

use Imbo\Exception\DatabaseException,
    Imbo\Auth\AccessControl\GroupQuery,
    Imbo\Model\Groups as GroupsModel,
    Imbo\Helpers\ObjectToArray,
    MongoDB\BSON\ObjectID,
    MongoDB\Driver\Command,
    MongoDB\Driver\Manager as DriverManager,
    MongoDB\Collection,
    MongoDB\Driver\Exception\Exception as MongoException;

/**
 * MongoDB access control adapter
 *
 * Valid parameters for this driver:
 *
 * - (string) databaseName Name of the database. Defaults to 'imbo'
 * - (string) server The server string to use when connecting to MongoDB. Defaults to
 *                   'mongodb://localhost:27017'
 * - (array) options Options to use when creating the driver manager instance.
 *                   Defaults to ['connectTimeoutMS' => 1000].
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Core\Auth\AccessControl\Adapter
 */
class MongoDB extends AbstractAdapter implements MutableAdapterInterface {
    /**
     * MongoDB driver manager instance
     *
     * @var MongoDB\Driver\Manager
     */
    private $driverManager;

    /**
     * The access control collection
     *
     * @var MongoDB\Collection
     */
    private $aclCollection;

    /**
     * The access control group collection
     *
     * @var MongoDB\Collection
     */
    private $aclGroupCollection;

    /**
     * Cached list of public keys details, keyed by public key
     *
     * @var array
     */
    private $publicKeys = [];

    /**
     * Cached list of resource groups, keyed by group name
     *
     * @var array
     */
    private $groups = [];

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = [
        // Database name
        'databaseName' => 'imbo',

        // Server string and ctor options
        'server'  => 'mongodb://localhost:27017',
        'options' => ['connectTimeoutMS' => 1000],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param MongoDB\Driver\Manager $manager Driver manager instance
     * @param MongoDB\Collection $collection Collection instance for the images
     */
    public function __construct(array $params = null, DriverManager $manager = null, Collection $collection = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($manager !== null) {
            $this->driverManager = $manager;
        }

        if ($collection !== null) {
            $this->collection = $collection;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups(GroupQuery $query = null, GroupsModel $model) {
        if ($query === null) {
            $query = new GroupQuery();
        }

        $collection = $this->getGroupsCollection();
        $cursor = $collection->find([], [
            'skip'  => ($query->page() - 1) * $query->limit(),
            'limit' => $query->limit()
        ]);

        $groups = [];
        foreach ($cursor as $group) {
            $groups[$group->name] = $group->resources;
        }

        // Cache the retrieved groups
        $this->groups = array_merge($this->groups, $groups);

        // Update model with total hits
        $model->setHits($collection->count());

        return $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup($groupName) {
        if (isset($this->groups[$groupName])) {
            return $this->groups[$groupName];
        }

        $group = $this->getGroupsCollection()->findOne([
            'name' => $groupName
        ]);

        if (isset($group->resources)) {
            $this->groups[$groupName] = $group->resources;
            return $group->resources;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey($publicKey) {
        $info = $this->getPublicKeyDetails($publicKey);

        if (!$info || !isset($info['privateKey'])) {
            return null;
        }

        return $info['privateKey'];
    }

    /**
     * {@inheritdoc}
     */
    public function addKeyPair($publicKey, $privateKey) {
        try {
            $result = $this->getAclCollection()->insertOne([
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
                'acl' => []
            ]);

            return (bool) $result->isAcknowledged();
        } catch (MongoException $e) {
            throw new DatabaseException('Could not insert key into database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deletePublicKey($publicKey) {
        try {
            $result = $this->getAclCollection()->deleteOne([
                'publicKey' => $publicKey
            ]);

            return (bool) $result->isAcknowledged();
        } catch (MongoException $e) {
            throw new DatabaseException('Could not delete key from database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updatePrivateKey($publicKey, $privateKey) {
        try {
            $result = $this->getAclCollection()->updateOne(
                ['publicKey' => $publicKey],
                ['$set' => ['privateKey' => $privateKey]]
            );

            return (bool) $result->isAcknowledged();
        } catch (MongoException $e) {
            throw new DatabaseException('Could not update key in database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessRule($publicKey, $accessId) {
        $rules = $this->getAccessListForPublicKey($publicKey);

        foreach ($rules as $rule) {
            if ($rule['id'] == $accessId) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function addAccessRule($publicKey, array $accessRule) {
        try {
            $result = $this->getAclCollection()->updateOne(
                ['publicKey' => $publicKey],
                ['$push' => ['acl' => array_merge(
                    ['id' => new ObjectID()],
                    $accessRule
                )]]
            );

            return (bool) $result->isAcknowledged();

        } catch (MongoException $e) {
            throw new DatabaseException('Could not update rule in database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAccessRule($publicKey, $accessId) {
        try {
            $result = $this->getAclCollection()->updateOne(
                ['publicKey' => $publicKey],
                [
                    '$pull' => [
                        'acl' => [
                            'id' => new ObjectID($accessId)
                        ]
                    ]
                ]
            );

            return (bool) $result->isAcknowledged();
        } catch (MongoException $e) {
            throw new DatabaseException('Could not delete rule from in database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addResourceGroup($groupName, array $resources = []) {
        try {
            $this->getGroupsCollection()->insertOne([
                'name' => $groupName,
                'resources' => $resources,
            ]);
        } catch (MongoException $e) {
            throw new DatabaseException('Could not add resource group to database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateResourceGroup($groupName, array $resources) {
        try {
            $this->getGroupsCollection()->updateOne([
                'name' => $groupName
            ], [
                '$set' => ['resources' => $resources],
            ]);
        } catch (MongoException $e) {
            throw new DatabaseException('Could not update resource group in database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteResourceGroup($groupName) {
        try {
            $success = (bool) $this->getGroupsCollection()->deleteOne([
                'name' => $groupName,
            ])->isAcknowledged();

            if ($success) {
                // Also remove ACL rules that depended on this group
                $this->getAclCollection()->updateMany(
                    ['acl.group' => $groupName],
                    [
                        '$pull' => [
                            'acl' => [
                                'group' => $groupName
                            ]
                        ]
                    ]
                );
            }

            return $success;
        } catch (MongoException $e) {
            throw new DatabaseException('Could not delete resource group from database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function publicKeyExists($publicKey) {
        return (bool) $this->getPublicKeyDetails($publicKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessListForPublicKey($publicKey) {
        $info = $this->getPublicKeyDetails($publicKey);

        if (!$info || !isset($info['acl'])) {
            return null;
        }

        return array_map(function($info) {
            $info['id'] = (string) $info['id'];

            return $info;
        }, $info['acl']);
    }

    /**
     * Get details for a given public key
     *
     * @param string $publicKey
     * @return array
     */
    private function getPublicKeyDetails($publicKey) {
        if (isset($this->publicKeys[$publicKey])) {
            return $this->publicKeys[$publicKey];
        }

        // Not in cache, fetch from database
        $pubkeyInfo = $this->getAclCollection()->findOne([
            'publicKey' => $publicKey
        ]);

        if ($pubkeyInfo) {
            $pubkeyInfo = ObjectToArray::toArray($pubkeyInfo);
            $this->publicKeys[$publicKey] = $pubkeyInfo;
        }

        return $pubkeyInfo;
    }

    /**
     * Get the ACL mongo collection
     *
     * @return MongoDB\Collection
     */
    private function getAclCollection() {
        if ($this->aclCollection === null) {
            try {
                $this->aclCollection = new Collection(
                    $this->getDriverManager(),
                    $this->getCollectionNamespace('accesscontrol')
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not select collection', 500, $e);
            }
        }

        return $this->aclCollection;
    }

    /**
     * Get the resource groups mongo collection
     *
     * @return MongoDB\Collection
     */
    private function getGroupsCollection() {
        if ($this->aclGroupCollection === null) {
            try {
                $this->aclGroupCollection = new Collection(
                    $this->getDriverManager(),
                    $this->getCollectionNamespace('accesscontrolgroup')
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not select collection', 500, $e);
            }
        }

        return $this->aclGroupCollection;
    }

    /**
     * Get the namespaced collection name for a given collection
     *
     * @param string $name Name of collection
     * @return string
     */
    private function getCollectionNamespace($name) {
        return $this->params['databaseName'] . '.' . $name;
    }

    /**
     * Get the mongo driver manager instance
     *
     * @return MongoDB\Driver\Manager
     */
    private function getDriverManager() {
        if ($this->driverManager === null) {
            try {
                $this->driverManager = new DriverManager(
                    $this->params['server'],
                    $this->params['options']
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not connect to database', 500, $e);
            }
        }

        return $this->driverManager;
    }
}
