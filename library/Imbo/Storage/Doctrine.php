<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Storage
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Storage;

use Imbo\Exception\StorageException,
    Imbo\Exception,
    Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Connection,
    DateTime;

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
 * @package Storage
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
    private $tableName = 'storage_images';

    /**
     * Doctrine connection
     *
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param Doctrine\DBAL\Connection $connection Optional connection instance. Primarily used for
     *                                             testing
     */
    public function __construct(array $params, Connection $connection = null) {
        $this->params = array_merge($this->params, $params);

        if ($connection !== null) {
            $this->setConnection($connection);
        }
    }

    /**
     * @see Imbo\Storage\StorageInterface::store()
     */
    public function store($publicKey, $imageIdentifier, $imageData) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('created')
              ->from($this->getTableName($publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch();

        if ($row) {
            $e = new StorageException('Image already exists', 400);
            $e->setImboErrorCode(Exception::IMAGE_ALREADY_EXISTS);

            throw $e;
        }

        return (boolean) $this->getConnection()->insert($this->getTableName($publicKey, $imageIdentifier), array(
            'publicKey'       => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'data'            => $imageData,
            'created'         => time(),
        ));
    }

    /**
     * @see Imbo\Storage\StorageInterface::delete()
     */
    public function delete($publicKey, $imageIdentifier) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('created')
              ->from($this->getTableName($publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new StorageException('File not found', 404);
        }

        $query->resetQueryParts();

        $query->delete($this->getTableName($publicKey, $imageIdentifier))
              ->where('publicKey = :publicKey')
              ->andWhere('imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        return (boolean) $query->execute();
    }

    /**
     * @see Imbo\Storage\StorageInterface::getImage()
     */
    public function getImage($publicKey, $imageIdentifier) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('data')
              ->from($this->getTableName($publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new StorageException('File not found', 404);
        }

        return $row['data'];
    }

    /**
     * @see Imbo\Storage\StorageInterface::getLastModified()
     */
    public function getLastModified($publicKey, $imageIdentifier) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('created')
              ->from($this->getTableName($publicKey, $imageIdentifier), 'i')
              ->where('publicKey = :publicKey')
              ->andWhere('imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new StorageException('File not found', 404);
        }

        $timestamp = (int) $row['created'];

        return new DateTime('@' . $timestamp);
    }

    /**
     * @see Imbo\Storage\StorageInterface::getStatus()
     */
    public function getStatus() {
        $connection = $this->getConnection();

        return $connection->isConnected() || $connection->connect();
    }

    /**
     * Set the connection instance
     *
     * @param Doctrine\DBAL\Connection $connection The connection instance
     * @return Imbo\Storage\Doctrine
     */
    private function setConnection(Connection $connection) {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the Doctrine connection
     *
     * @return Doctrine\DBAL\Connection
     */
    private function getConnection() {
        if ($this->connection === null) {
            $this->connection = DriverManager::getConnection($this->params, new Configuration());
        }

        return $this->connection;
    }

    /**
     * Method that can be overridden to dynamically select table names based on the public key and
     * the image identifier.
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier to fetch
     * @return string Returns a table name where the image is located
     */
    protected function getTableName($publicKey, $imageIdentifier) {
        return $this->tableName;
    }
}
