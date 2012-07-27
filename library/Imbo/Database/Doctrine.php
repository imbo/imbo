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
 * @package Database
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Database;

use Imbo\Image\ImageInterface,
    Imbo\Resource\Images\QueryInterface,
    Imbo\Exception\DatabaseException,
    Imbo\Exception,
    Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Connection,
    PDO,
    DateTime;

/**
 * Doctrine 2 database driver
 *
 * Parameters for this driver:
 *
 * - <pre>(string) dbname</pre> Name of the database to connect to
 * - <pre>(string) user</pre> Username to use when connecting
 * - <pre>(string) password</pre> Password to use when connecting
 * - <pre>(string) host</pre> Hostname to use when connecting
 * - <pre>(string) driver</pre> Which driver to use
 *
 * @package Database
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Doctrine implements DatabaseInterface {
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
     * Default table names for the database
     *
     * @var array
     */
    private $tableNames = array(
        'imageinfo' => 'imageinfo',
        'metadata'  => 'metadata',
    );

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
    public function insertImage($publicKey, $imageIdentifier, ImageInterface $image) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('id')
              ->from($this->getTableName('imageinfo', $publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
        ));

        $stmt = $query->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $e = new DatabaseException('Image already exists', 400);
            $e->setImboErrorCode(Exception::IMAGE_ALREADY_EXISTS);

            throw $e;
        }

        $now = time();

        return (boolean) $this->getConnection()->insert($this->getTableName('imageinfo', $publicKey, $imageIdentifier), array(
            'size'            => $image->getFilesize(),
            'publicKey'       => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'extension'       => $image->getExtension(),
            'mime'            => $image->getMimeType(),
            'added'           => $now,
            'updated'         => $now,
            'width'           => $image->getWidth(),
            'height'          => $image->getHeight(),
            'checksum'        => md5($image->getBlob()),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImage($publicKey, $imageIdentifier) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('id')
              ->from($this->getTableName('imageinfo', $publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new DatabaseException('Image not found', 404);
        }

        $query->resetQueryParts();
        $query->delete($this->getTableName('imageinfo', $publicKey, $imageIdentifier))
              ->where('id = :id')
              ->setParameters(array(
                  ':id' => $row['id'],
              ))->execute();

        $query->resetQueryParts();
        $query->delete($this->getTableName('metadata', $publicKey, $imageIdentifier))
              ->where('imageId = :imageId')
              ->setParameters(array(
                  ':imageId' => $row['id'],
              ))->execute();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMetadata($publicKey, $imageIdentifier, array $metadata) {
        // Fetch the current connection
        $connection = $this->getConnection();
        $imageId = $this->getImageId($publicKey, $imageIdentifier);

        // Fetch existing metadata so we can merge it with the data passed to this method
        $existing = $this->getMetadata($publicKey, $imageIdentifier);
        $metadata = array_merge($existing, $metadata);

        // Delete existing metadata
        $this->deleteMetadata($publicKey, $imageIdentifier);

        // Insert merged metadata
        foreach ($metadata as $key => $value) {
            $connection->insert($this->getTableName('metadata', $publicKey, $imageIdentifier), array(
                'imageId'  => $imageId,
                'tagName'  => $key,
                'tagValue' => $value,
            ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($publicKey, $imageIdentifier) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('id')
              ->from($this->getTableName('imageinfo', $publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new DatabaseException('Image not found', 404);
        }

        $query->resetQueryParts();
        $query->select('tagName', 'tagValue')
              ->from($this->getTableName('metadata', $publicKey, $imageIdentifier), 'm')
              ->where('imageId = :imageId')
              ->setParameters(array(':imageId' => $row['id']));

        $stmt = $query->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $metadata = array();

        foreach ($rows as $row) {
            $metadata[$row['tagName']] = $row['tagValue'];
        }

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMetadata($publicKey, $imageIdentifier) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('id')
              ->from($this->getTableName('imageinfo', $publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new DatabaseException('Image not found', 404);
        }

        $query->resetQueryParts();
        $query->delete($this->getTableName('metadata', $publicKey, $imageIdentifier))
              ->where('imageId = :imageId')
              ->setParameters(array(
                  ':imageId' => $row['id'],
                ))->execute();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages($publicKey, QueryInterface $query) {
        $images = array();

        $qb = $this->getConnection()->createQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName('imageinfo', $publicKey), 'i')
           ->orderBy('added', 'DESC');

        $from = $query->from();
        $to = $query->to();

        if ($from || $to) {
            if ($from !== null) {
                $qb->where('added >= :from')->setParameter(':from', $from);
            }

            if ($to !== null) {
                $qb->andWhere('added <= :to')->setParameter(':to', $to);
            }
        }

        if ($limit = $query->limit()) {
            $qb->setMaxResults($limit);
        }

        if ($page = $query->page()) {
            $offset = (int) $query->limit() * ($page - 1);
            $qb->setFirstResult($offset);
        }

        $stmt = $qb->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $returnMetadata = $query->returnMetadata();

        foreach ($rows as $row) {
            $image = array(
                'extension'       => $row['extension'],
                'added'           => $row['added'],
                'checksum'        => $row['checksum'],
                'updated'         => $row['updated'],
                'publicKey'       => $publicKey,
                'imageIdentifier' => $row['imageIdentifier'],
                'mime'            => $row['mime'],
                'size'            => $row['size'],
                'width'           => $row['width'],
                'height'          => $row['height']
            );

            if ($returnMetadata) {
                $image['metadata'] = $this->getMetadata($publicKey, $row['imageIdentifier']);
            }

            $images[] = $image;
        }

        return $images;
    }

    /**
     * {@inheritdoc}
     */
    public function load($publicKey, $imageIdentifier, ImageInterface $image) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('*')
              ->from($this->getTableName('imageinfo', $publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
        ));

        $stmt = $query->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new DatabaseException('Image not found', 404);
        }

        $image->setWidth($row['width'])
              ->setHeight($row['height'])
              ->setMimeType($row['mime'])
              ->setExtension($row['extension']);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($publicKey, $imageIdentifier = null) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('updated')
              ->from($this->getTableName('imageinfo', $publicKey), 'i')
              ->where('i.publicKey = :publicKey')
              ->setParameter(':publicKey', $publicKey);

        if ($imageIdentifier) {
            $query->andWhere('i.imageIdentifier = :imageIdentifier')
                  ->setParameter(':imageIdentifier', $imageIdentifier);
        }

        $stmt = $query->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row && $imageIdentifier) {
            throw new DatabaseException('Image not found', 404);
        } else if (!$row) {
            $row = array('updated' => time());
        }

        return new DateTime('@' . $row['updated']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNumImages($publicKey) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('COUNT(i.id)')
              ->from($this->getTableName('imageinfo', $publicKey), 'i')
              ->where('i.publicKey = :publicKey')
              ->setParameter(':publicKey', $publicKey);

        $stmt = $query->execute();

        return (int) $stmt->fetchColumn();
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
    public function getImageMimeType($publicKey, $imageIdentifier) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('mime')
              ->from($this->getTableName('imageinfo', $publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $mime = $stmt->fetchColumn();

        if (!$mime) {
            throw new DatabaseException('Image not found', 404);
        }

        return $mime;
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
     * Get the internal image ID
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier
     * @return int
     */
    private function getImageId($publicKey, $imageIdentifier) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('i.id')
              ->from($this->getTableName('imageinfo', $publicKey, $imageIdentifier), 'i')
              ->where('i.publicKey = :publicKey')
              ->andWhere('i.imageIdentifier = :imageIdentifier')
              ->setParameters(array(
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
              ));

        $stmt = $query->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $row['id'];
    }

    /**
     * Method that can be overridden to dynamically select table names based on the public key and
     * the image identifier.
     *
     * @param string $type The type of the table. Either "metadata" or "imageinfo"
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier to fetch
     * @return string Returns a table name where the image is located
     */
    protected function getTableName($type, $publicKey, $imageIdentifier = null) {
        return $this->tableNames[$type];
    }
}
