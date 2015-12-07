<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Model\Image,
    Imbo\Model\Images,
    Imbo\Resource\Images\Query,
    Imbo\Exception\DatabaseException,
    Imbo\Helpers\ObjectToArray,
    MongoDB\Driver\Manager as DriverManager,
    MongoDB\Collection,
    MongoDB\Driver\Exception\Exception as MongoException;

/**
 * MongoDB database driver for the image variations
 *
 * Valid parameters for this driver:
 *
 * - (string) databaseName Name of the database. Defaults to 'imbo'
 * - (string) server The server string to use when connecting to MongoDB. Defaults to
 *                   'mongodb://localhost:27017'
 * - (array) options Options to use when creating the driver manager instance.
 *                   Defaults to ['connectTimeoutMS' => 1000].
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Database
 */
class MongoDB implements DatabaseInterface {
    /**
     * Mongo client instance
     *
     * @var MongoDB\Driver\Manager
     */
    private $driverManager;

    /**
     * The imagevariation collection
     *
     * @var MongoDB\Collection
     */
    private $collection;

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
     * @param MongoDB\Driver\Manager $manager MongoDB driver manager instance
     * @param MongoDB\Collection $collection MongoDB collection instance for the image variation collection
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
    public function storeImageVariationMetadata($user, $imageIdentifier, $width, $height) {
        try {
            $this->getCollection()->insertOne([
                'added' => time(),
                'user' => $user,
                'imageIdentifier'  => $imageIdentifier,
                'width' => $width,
                'height' => $height,
            ]);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to save image variation data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getBestMatch($user, $imageIdentifier, $width) {
        $query = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
            'width' => [
                '$gte' => $width,
            ],
        ];

        $result = $this->getCollection()->findOne($query, [
            'sort' => ['width' => 1],
            'projection' => [
                '_id' => false,
                'width' => true,
                'height' => true,
            ]
        ]);

        return $result ? ObjectToArray::toArray($result) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImageVariations($user, $imageIdentifier, $width = null) {
        $query = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ];

        if ($width !== null) {
            $query['width'] = $width;
        }

        $this->getCollection()->deleteMany($query);

        return true;
    }

    /**
     * Get the mongo collection
     *
     * @return MongoDB\Collection
     */
    private function getCollection() {
        if ($this->collection === null) {
            try {
                $this->collection = new Collection(
                    $this->getDriverManager(),
                    $this->getCollectionNamespace('imagevariation')
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not select collection', 500, $e);
            }
        }

        return $this->collection;
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
     * Get the mongo client instance
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
