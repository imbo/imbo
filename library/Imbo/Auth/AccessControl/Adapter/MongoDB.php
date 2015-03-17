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
    Imbo\Exception\RuntimeException,
    Imbo\Auth\AccessControl\UserQuery,
    Imbo\Auth\AccessControl\GroupQuery,
    MongoClient,
    MongoCollection,
    MongoException,
    MongoId;

/**
 * MongoDB access control adapter
 *
 * Valid parameters for this driver:
 *
 * - (string) databaseName Name of the database. Defaults to 'imbo'
 * - (string) server The server string to use when connecting to MongoDB. Defaults to
 *                   'mongodb://localhost:27017'
 * - (array) options Options to use when creating the MongoClient instance. Defaults to
 *                   ['connect' => true, 'connectTimeoutMS' => 1000].
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Core\Auth\AccessControl\Adapter
 */
class MongoDB extends AbstractAdapter implements MutableAdapterInterface {
    /**
     * Mongo client instance
     *
     * @var MongoClient
     */
    private $mongoClient;

    /**
     * The access control collection
     *
     * @var MongoCollection
     */
    private $aclCollection;

    /**
     * The access control group collection
     *
     * @var MongoCollection
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
    private $resourceGroups = [];

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
        'options' => ['connect' => true, 'connectTimeoutMS' => 1000],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param MongoClient $client MongoClient instance
     * @param MongoCollection $collection MongoCollection instance for the image variation collection
     */
    public function __construct(array $params = null, MongoClient $client = null, MongoCollection $collection = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($client !== null) {
            $this->mongoClient = $client;
        }

        if ($collection !== null) {
            $this->collection = $collection;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccess($publicKey, $resource, $user = null) {
        $accessList = $this->getAccessListForPublicKey($publicKey);

        foreach ($accessList as $acl) {
            // If a user is specified, ensure the public key has access to the user
            $userAccess = !$user || isset($acl['users']) && in_array($user, $acl['users']);
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
    public function getGroups(GroupQuery $query = null) {
        $cursor = $this->getGroupsCollection()
            ->find()
            ->skip($query->offset() ?: 0)
            ->limit($query->limit());

        $groups = [];
        foreach ($cursor as $group) {
            $groups[$group['name']] = $group['resources'];
        }

        return $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup($groupName) {
        $group = $this->getGroupsCollection()->findOne([
            'name' => $groupName
        ]);

        if (isset($group['resources'])) {
            return $group['resources'];
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
            $result = $this->getAclCollection()->insert([
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
                'acl' => []
            ]);

            return (bool) $result['ok'];

        } catch (MongoException $e) {
            throw new DatabaseException('Could not insert key into database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deletePublicKey($publicKey) {
        try {
            $result = $this->getAclCollection()->remove([
                'publicKey' => $publicKey
            ]);

            return (bool) $result['ok'];

        } catch (MongoException $e) {
            throw new DatabaseException('Could not delete key from database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updatePrivateKey($publicKey, $privateKey) {
        try {
            $result = $this->getAclCollection()->update(
                ['publicKey' => $publicKey],
                ['$set' => ['privateKey' => $privateKey]]
            );

            return (bool) $result['ok'];

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
            $result = $this->getAclCollection()->update(
                ['publicKey' => $publicKey],
                ['$push' => ['acl' => array_merge(
                    ['id' => new MongoId()],
                    $accessRule
                )]]
            );

            return (bool) $result['ok'];

        } catch (MongoException $e) {
            throw new DatabaseException('Could not update rule in database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAccessRule($publicKey, $accessId) {
        try {
            $result = $this->getAclCollection()->update(
                ['publicKey' => $publicKey],
                [
                    '$pull' => [
                        'acl' => [
                            'id' => new MongoId($accessId)
                        ]
                    ]
                ]
            );

            return (bool) $result['ok'];
        } catch (MongoException $e) {
            throw new DatabaseException('Could not delete rule from in database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addResourceGroup($groupName, array $resources = []) {
        throw new \Exception('NOT IMPLEMENTED YET');
    }

    /**
     * {@inheritdoc}
     */
    public function addResourcesToGroup($groupName, array $resources) {
        throw new \Exception('NOT IMPLEMENTED YET');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteResourceGroup($groupName) {
        throw new \Exception('NOT IMPLEMENTED YET');
    }

    /**
     * {@inheritdoc}
     */
    public function publicKeyExists($publicKey) {
        return (bool) $this->getPublicKeyDetails($publicKey);
    }

    /**
     * Get the access control list for a given public key
     *
     * @param  string $publicKey
     * @return array
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

        return $info['acl'];
    }

    /**
     * Get details for a given public key
     *
     * @param  string $publicKey
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
            $this->publicKeys[$publicKey] = $pubkeyInfo;
        }

        return $pubkeyInfo;
    }

    /**
     * Get the ACL mongo collection
     *
     * @return MongoCollection
     */
    private function getAclCollection() {
        if ($this->aclCollection === null) {
            try {
                $this->aclCollection = $this->getMongoClient()->selectCollection(
                    $this->params['databaseName'],
                    'accesscontrol'
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
     * @return MongoCollection
     */
    private function getGroupsCollection() {
        if ($this->aclGroupCollection === null) {
            try {
                $this->aclGroupCollection = $this->getMongoClient()->selectCollection(
                    $this->params['databaseName'],
                    'accesscontrolgroups'
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not select collection', 500, $e);
            }
        }

        return $this->aclGroupCollection;
    }

    /**
     * Get the mongo client instance
     *
     * @return MongoClient
     */
    private function getMongoClient() {
        if ($this->mongoClient === null) {
            try {
                $this->mongoClient = new MongoClient($this->params['server'], $this->params['options']);
            } catch (MongoException $e) {
                throw new DatabaseException('Could not connect to database', 500, $e);
            }
        }

        return $this->mongoClient;
    }
}
