<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\ImageVariations\Storage;

use Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Connection;

/**
 * Doctrine 2 image variations storage driver
 *
 * Refer to http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest for configuration parameters
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Storage
 */
class Doctrine implements StorageInterface {
    /**
     * Parameters for the Doctrine connection
     *
     * @var array
     */
    private $params = [];

    /**
     * Name of the table used for storing the images
     *
     * @var string
     */
    private $tableName = 'storage_image_variations';

    /**
     * Doctrine connection
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param \Doctrine\DBAL\Connection $connection Optional connection instance
     */
    public function __construct(array $params, Connection $connection = null) {
        $this->params = $params;

        if (isset($this->params['pdo'])) {
            trigger_error(
                sprintf(
                    'The usage of pdo in the configuration array for %s is deprecated and will be removed in Imbo-3.x',
                    __CLASS__
                ),
                E_USER_DEPRECATED
            );
        }

        if ($connection !== null) {
            trigger_error(
                sprintf(
                    'Specifying a connection instance in %s is deprecated and will be removed in Imbo-3.x',
                    __CLASS__
                ),
                E_USER_DEPRECATED
            );
            $this->setConnection($connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeImageVariation($user, $imageIdentifier, $blob, $width) {
        return (boolean) $this->getConnection()->insert($this->getTableName($user, $imageIdentifier), [
            'user'            => $user,
            'imageIdentifier' => $imageIdentifier,
            'data'            => $blob,
            'width'           => (int) $width,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getImageVariation($user, $imageIdentifier, $width) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('data')
              ->from($this->getTableName($user, $imageIdentifier), 'i')
              ->where('user = :user')
              ->andWhere('imageIdentifier = :imageIdentifier')
              ->andWhere('width = :width')
              ->setParameters([
                  ':user'            => $user,
                  ':imageIdentifier' => $imageIdentifier,
                  ':width'           => (int) $width,
              ]);

        $stmt = $query->execute();
        $row = $stmt->fetch();

        return $row ? $row['data'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImageVariations($user, $imageIdentifier, $width = null) {
        $query = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier
        ];

        if ($width !== null) {
            $query['width'] = $width;
        }

        $tableName = $this->getTableName($user, $imageIdentifier);
        return (boolean) $this->getConnection()->delete($tableName, $query);
    }

    /**
     * Set the connection instance
     *
     * @param \Doctrine\DBAL\Connection $connection The connection instance
     * @return Doctrine
     */
    private function setConnection(Connection $connection) {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the Doctrine connection
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection() {
        if ($this->connection === null) {
            $this->connection = DriverManager::getConnection($this->params, new Configuration());
        }

        return $this->connection;
    }

    /**
     * Method that can be overridden to dynamically select table names based on the user and the
     * image identifier. The default implementation does not use them for anything, and simply
     * returns the default table name.
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier to fetch
     * @return string Returns a table name where the image is located
     */
    protected function getTableName($user, $imageIdentifier) {
        return $this->tableName;
    }
}
