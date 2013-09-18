<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Database;

use Imbo\Model\Image,
    Imbo\Resource\Images\Query,
    Imbo\Exception\DatabaseException,
    Imbo\Exception,
    Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Connection,
    PDO,
    DateTime,
    DateTimeZone;

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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Database
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
        'shorturl'  => 'shorturl',
    );

    /**
     * Doctrine connection
     *
     * @var Connection
     */
    private $connection;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param Connection $connection Optional connection instance. Primarily used for testing
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
    public function insertImage($publicKey, $imageIdentifier, Image $image) {
        $now = time();

        if ($added = $image->getAddedDate()) {
            $added = $added->getTimestamp();
        }

        if ($updated = $image->getUpdatedDate()) {
            $updated = $updated->getTimestamp();
        }

        if ($id = $this->getImageId($publicKey, $imageIdentifier)) {
            return (boolean) $this->getConnection()->update($this->tableNames['imageinfo'], array(
                'updated' => $now,
            ), array(
                'id' => $id
            ));
        }

        return (boolean) $this->getConnection()->insert($this->tableNames['imageinfo'], array(
            'size'            => $image->getFilesize(),
            'publicKey'       => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'extension'       => $image->getExtension(),
            'mime'            => $image->getMimeType(),
            'added'           => $added ?: $now,
            'updated'         => $updated ?: $now,
            'width'           => $image->getWidth(),
            'height'          => $image->getHeight(),
            'checksum'        => md5($image->getBlob()),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImage($publicKey, $imageIdentifier) {
        if (!$id = $this->getImageId($publicKey, $imageIdentifier)) {
            throw new DatabaseException('Image not found', 404);
        }

        $query = $this->getConnection()->createQueryBuilder();
        $query->delete($this->tableNames['imageinfo'])
              ->where('id = :id')
              ->setParameters(array(
                  ':id' => $id,
              ))->execute();

        $query->resetQueryParts();
        $query->delete($this->tableNames['metadata'])
              ->where('imageId = :imageId')
              ->setParameters(array(
                  ':imageId' => $id,
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
            $connection->insert($this->tableNames['metadata'], array(
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
        if (!$id = $this->getImageId($publicKey, $imageIdentifier)) {
            throw new DatabaseException('Image not found', 404);
        }

        $query = $this->getConnection()->createQueryBuilder();
        $query->select('tagName', 'tagValue')
              ->from($this->tableNames['metadata'], 'm')
              ->where('imageId = :imageId')
              ->setParameters(array(':imageId' => $id));

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
        if (!$id = $this->getImageId($publicKey, $imageIdentifier)) {
            throw new DatabaseException('Image not found', 404);
        }

        $query = $this->getConnection()->createQueryBuilder();
        $query->delete($this->tableNames['metadata'])
              ->where('imageId = :imageId')
              ->setParameters(array(
                  ':imageId' => $id,
                ))->execute();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages($publicKey, Query $query) {
        $images = array();

        $qb = $this->getConnection()->createQueryBuilder();
        $qb->select('*')
           ->from($this->tableNames['imageinfo'], 'i')
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

        if ($metadataQuery = $query->metadataQuery()) {
            $qb->leftJoin('i', 'metadata', 'm', 'i.id = m.imageId');
            $tmp = 0;

            foreach ($metadataQuery as $key => $value) {
                $qb->andWhere($qb->expr()->andx(
                    $qb->expr()->eq('m.tagName', ':tagName' . $tmp),
                    $qb->expr()->eq('m.tagValue', ':tagValue' . $tmp)
                ));
                $qb->setParameters(array(
                    ':tagName' . $tmp => $key,
                    ':tagValue' . $tmp => $value,
                ));
                $tmp++;
            }
        }

        if ($imageIdentifiers = $query->imageIdentifiers()) {
            $expr = $qb->expr();
            $composite = $expr->orX();

            foreach ($imageIdentifiers as $i => $id) {
                $composite->add($expr->eq('i.imageIdentifier', ':imageIdentifier' . $i));
                $qb->setParameter(':imageIdentifier' . $i, $id);
            }

            $qb->andWhere($composite);
        }

        $stmt = $qb->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $returnMetadata = $query->returnMetadata();

        foreach ($rows as $row) {
            $image = array(
                'extension'       => $row['extension'],
                'added'           => new DateTime('@' . $row['added'], new DateTimeZone('UTC')),
                'updated'         => new DateTime('@' . $row['updated'], new DateTimeZone('UTC')),
                'checksum'        => $row['checksum'],
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
    public function load($publicKey, $imageIdentifier, Image $image) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('*')
              ->from($this->tableNames['imageinfo'], 'i')
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
              ->setExtension($row['extension'])
              ->setAddedDate(new DateTime('@' . $row['added'], new DateTimeZone('UTC')))
              ->setUpdatedDate(new DateTime('@' . $row['updated'], new DateTimeZone('UTC')));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($publicKey, $imageIdentifier = null) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('updated')
              ->from($this->tableNames['imageinfo'], 'i')
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

        return new DateTime('@' . $row['updated'], new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getNumImages($publicKey) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('COUNT(i.id)')
              ->from($this->tableNames['imageinfo'], 'i')
              ->where('i.publicKey = :publicKey')
              ->setParameter(':publicKey', $publicKey);

        $stmt = $query->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function getNumBytes($publicKey) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('SUM(i.size)')
              ->from($this->tableNames['imageinfo'], 'i')
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
              ->from($this->tableNames['imageinfo'], 'i')
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
     * {@inheritdoc}
     */
    public function imageExists($publicKey, $imageIdentifier) {
        return (boolean) $this->getImageId($publicKey, $imageIdentifier);
    }

    /**
     * {@inheritdoc}
     */
    public function insertShortUrl($shortUrlId, $publicKey, $imageIdentifier, $extension = null, array $query = array()) {
        return (boolean) $this->getConnection()->insert($this->tableNames['shorturl'], array(
            'shortUrlId' => $shortUrlId,
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'extension' => $extension,
            'query' => serialize($query),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getShortUrlParams($shortUrlId) {
        $qb = $this->getConnection()->createQueryBuilder();
        $qb->select('publicKey', 'imageIdentifier', 'extension', 'query')
           ->from($this->tableNames['shorturl'], 's')
           ->where('shortUrlId = :shortUrlId')
           ->setParameters(array(':shortUrlId' => $shortUrlId));

        $stmt = $qb->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['query'] = unserialize($row['query']);

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortUrlId($publicKey, $imageIdentifier, $extension = null, array $query = array()) {
        $qb = $this->getConnection()->createQueryBuilder();
        $qb->select('shortUrlId')
           ->from($this->tableNames['shorturl'], 's')
           ->where('publicKey = :publicKey')
           ->andWhere('imageIdentifier = :imageIdentifier')
           ->andWhere('query = :query')
           ->setParameters(array(
               ':publicKey' => $publicKey,
               ':imageIdentifier' => $imageIdentifier,
               ':query' => serialize($query),
           ));

        if ($extension === null) {
            $qb->andWhere('extension is NULL');
        } else {
            $qb->andWhere('extension = :extension')
               ->setParameter(':extension', $extension);
        }

        $stmt = $qb->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $row['shortUrlId'];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteShortUrls($publicKey, $imageIdentifier) {
        $qb = $this->getConnection()->createQueryBuilder();

        return (boolean) $qb->delete($this->tableNames['shorturl'])
                            ->where('publicKey = :publicKey')
                            ->andWhere('imageIdentifier = :imageIdentifier')
                            ->setParameters(array(
                                ':publicKey' => $publicKey,
                                ':imageIdentifier' => $imageIdentifier,
                            ))->execute();
    }

    /**
     * Set the connection instance
     *
     * @param Connection $connection The connection instance
     * @return self
     */
    private function setConnection(Connection $connection) {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the Doctrine connection
     *
     * @return Connection
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
              ->from($this->tableNames['imageinfo'], 'i')
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
}
