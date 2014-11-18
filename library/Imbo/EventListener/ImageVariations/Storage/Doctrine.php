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
 * Parameters for this driver:
 *
 * - <pre>(string) dbname</pre> Name of the database to connect to
 * - <pre>(string) user</pre> Username to use when connecting
 * - <pre>(string) password</pre> Password to use when connecting
 * - <pre>(string) host</pre> Hostname to use when connecting
 * - <pre>(string) driver</pre> Which driver to use
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
    private $params = array(
        'dbname'   => null,
        'user'     => null,
        'password' => null,
        'host'     => null,
        'driver'   => null,
    );

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
        $this->params = array_merge($this->params, $params);

        if ($connection !== null) {
            $this->setConnection($connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeImageVariation($publicKey, $imageIdentifier, $blob, $width) {
        return (boolean) $this->getConnection()->insert($this->getTableName($publicKey, $imageIdentifier), [
            'publicKey'       => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'data'            => $blob,
            'width'           => (int) $width,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getImageVariation($publicKey, $imageIdentifier, $width) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('data')
              ->from($this->getTableName($publicKey, $imageIdentifier), 'i')
              ->where('publicKey = :publicKey')
              ->andWhere('imageIdentifier = :imageIdentifier')
              ->andWhere('width = :width')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
                  ':width'           => (int) $width,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch();

        return $row ? $row['data'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImageVariations($publicKey, $imageIdentifier, $width = null) {
        $query = [
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier
        ];

        if ($width !== null) {
            $query['width'] = $width;
        }

        $tableName = $this->getTableName($publicKey, $imageIdentifier);
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
     * Method that can be overridden to dynamically select table names based on the public key and
     * the image identifier. The default implementation does not use them for anything, and simply
     * returns the default table name.
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier to fetch
     * @return string Returns a table name where the image is located
     */
    protected function getTableName($publicKey, $imageIdentifier) {
        return $this->tableName;
    }
}
