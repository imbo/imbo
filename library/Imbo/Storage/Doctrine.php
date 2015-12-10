<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Storage;

use Imbo\Exception\StorageException,
    Imbo\Exception,
    Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Connection,
    DateTime,
    DateTimeZone;

/**
 * Doctrine 2 storage driver
 *
 * Parameters for this driver:
 *
 * - <pre>(string) dbname</pre> Name of the database to connect to
 * - <pre>(string) user</pre> Username to use when connecting
 * - <pre>(string) password</pre> Password to use when connecting
 * - <pre>(string) host</pre> Hostname to use when connecting
 * - <pre>(string) driver</pre> Which driver to use
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Storage
 */
class Doctrine implements StorageInterface {
    /**
     * Parameters for the Doctrine connection
     *
     * @var array
     */
    private $params = [
        'dbname'   => null,
        'user'     => null,
        'password' => null,
        'host'     => null,
        'driver'   => null,
    ];

    /**
     * Name of the table used for storing the images
     *
     * @var string
     */
    private $tableName = 'storage_images';

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
     * @param \Doctrine\DBAL\Connection $connection Optional connection instance. Primarily used
     *                                              for testing
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
    public function store($user, $imageIdentifier, $imageData) {
        $now = time();

        if ($this->imageExists($user, $imageIdentifier)) {
            return (boolean) $this->getConnection()->update($this->getTableName($user, $imageIdentifier), [
                'updated' => $now,
            ], [
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
            ]);
        }

        $inserted = $this->getConnection()->insert($this->getTableName($user, $imageIdentifier), [
            'user'            => $user,
            'imageIdentifier' => $imageIdentifier,
            'data'            => $imageData,
            'updated'         => $now,
        ]);

        if (!$inserted) {
            throw new StorageException('Unable to persist image data to database.', 500);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($user, $imageIdentifier) {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        return (boolean) $this->getConnection()->delete($this->getTableName($user, $imageIdentifier), [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getImage($user, $imageIdentifier) {
        return $this->getField($user, $imageIdentifier, 'data');
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($user, $imageIdentifier) {
        $timestamp = (int) $this->getField($user, $imageIdentifier, 'updated');

        return new DateTime('@' . $timestamp, new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        $connection = $this->getConnection();

        return $connection->isConnected() || $connection->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function imageExists($user, $imageIdentifier) {
        try {
            return (boolean) $this->getField($user, $imageIdentifier, 'user');
        } catch (StorageException $e) {
            return false;
        }
    }

    /**
     * Fetch a field from the image table
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param string $field The field to fetch
     */
    private function getField($user, $imageIdentifier, $field) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select($field)
              ->from($this->getTableName($user, $imageIdentifier), 'i')
              ->where('user = :user')
              ->andWhere('imageIdentifier = :imageIdentifier')
              ->setParameters([
                  ':user'            => $user,
                  ':imageIdentifier' => $imageIdentifier,
              ]);

        $stmt = $query->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new StorageException('File not found', 404);
        }

        return $row[$field];
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
