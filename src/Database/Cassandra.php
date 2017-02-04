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
    Imbo\Model\Images,
    Imbo\Resource\Images\Query,
    Cassandra as CassandraLib,
    Cassandra\Session,
    Cassandra\ExecutionOptions,
    Cassandra\SimpleStatement,

    Imbo\Exception\DatabaseException,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Exception,
    DateTime,
    DateTimeZone;

/**
 * Cassandra database driver
 *
 * Parameters for this driver:
 *
 * - <pre>(string> keyspace</pre> Name of keyspace to use (default: imbo)
 * - <pre>(string) hosts</pre> Array of ips/hosts of cassandra notes to bootstrap from (default: [localhost])
 * - <pre>(string) persistent</pre> Whether the session should be persistent across requests (default: true)
 * - <pre>(Cassandra\Session) cluster</pre> Existing Cassandra session (from Cassandra::cluster()->build() with custom parameters)
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Database
 */
class Cassandra implements DatabaseInterface {
    public function __construct($params = []) {
        $session = null;

        if (!empty($params['session']) && $params['session'] instanceof Session) {
            $session = $params['session'];
        } else {
            $cluster = CassandraLib::cluster();

            if (!empty($params['hosts'])) {
                if (is_array($params['hosts'])) {
                    $cluster = call_user_func_array([$cluster, 'withContactPoints'], $params['hosts']);
                } else {
                    $cluster = $cluster->withContactPoints($params['hosts']);
                }
            }

            if (!empty($params['persistent'])) {
                $cluster = $cluster->withPersistentSessions((bool) $params['persistent']);
            }

            $session = $cluster->build()->connect(!empty($params['keyspace']) ? $params['keyspace'] : 'imbo');
        }

        $this->session = $session;
    }

    protected function execute($statement, $args = []) {
        $options = new ExecutionOptions(['arguments' => $args]);
        return $this->session->execute($statement, $options);
    }

    /**
     * @inheritDoc
     */
    public function insertImage($user, $imageIdentifier, Image $image) {
        $now = time();

        if ($added = $image->getAddedDate()) {
            $added = $added->getTimestamp();
        }

        if ($updated = $image->getUpdatedDate()) {
            $updated = $updated->getTimestamp();
        }

        $statement = $this->session->prepare("
            INSERT INTO
                imageinfo 
            (
                user,
                imageIdentifier,
                size,
                extension,
                mime,
                added,
                updated,
                width,
                height,
                checksum,
                originalChecksum            
            )
            VALUES
            (
                :user, :imageIdentifier, :size, :extension, :mime, :added, :updated, :width, :height, :checksum, :originalChecksum
            )
        ");

        $args = [
            'size'             => $image->getFilesize(),
            'user'             => $user,
            'imageIdentifier'  => $imageIdentifier,
            'extension'        => $image->getExtension(),
            'mime'             => $image->getMimeType(),
            'added'            => $added ?: $now,
            'updated'          => $updated ?: $now,
            'width'            => $image->getWidth(),
            'height'           => $image->getHeight(),
            'checksum'         => $image->getChecksum(),
            'originalChecksum' => $image->getOriginalChecksum(),
        ];

        $result = (boolean) $this->execute($statement, $args);

        // update usermeta
        $statement = $this->session->prepare("
            UPDATE
                usermeta
            SET
                last_updated = ?
            WHERE
                user = ?
        ");

        $this->execute($statement, ['last_updated' => new CassandraLib\Timestamp(), 'user' => $user]);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function deleteImage($user, $imageIdentifier) {
        // getImageProperties will usually throw the same exception
        if (!$row = $this->getImageProperties($user, $imageIdentifier)) {
            throw new DatabaseException("Image not found", 404);
        }

        $statement = $this->session->prepare("
            DELETE FROM 
                imageinfo
            WHERE
                user = :user AND 
                imageIdentifier = :imageIdentifier
        ");

        $args = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ];

        return (boolean) $this->execute($statement, $args);
    }

    /**
     * @inheritDoc
     */
    public function updateMetadata($user, $imageIdentifier, array $metadata) {
        // TODO: Implement updateMetadata() method.
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($user, $imageIdentifier) {
        // TODO: Implement getMetadata() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteMetadata($user, $imageIdentifier) {
        // TODO: Implement deleteMetadata() method.
    }

    /**
     * @inheritDoc
     */
    public function getImages(array $users, Query $query, Images $model) {
        // TODO: Implement getImages() method.
    }

    /**
     * @inheritDoc
     */
    public function load($user, $imageIdentifier, Image $image) {
        $row = $this->getImageProperties($user, $imageIdentifier);

        $image->setWidth($row['width'])
            ->setHeight($row['height'])
            ->setFilesize($row['size'])
            ->setMimeType($row['mime'])
            ->setExtension($row['extension'])
            ->setAddedDate(new DateTime('@' . $row['added'], new DateTimeZone('UTC')))
            ->setUpdatedDate(new DateTime('@' . $row['updated'], new DateTimeZone('UTC')));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getImageProperties($user, $imageIdentifier) {
        $statement = $this->session->prepare("
            SELECT
                *
            FROM
                imageinfo
            WHERE
                user = :user AND 
                imageIdentifier = :imageIdentifier
        ");

        $args = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ];

        $rows = $this->execute($statement, $args);

        // count() gives int(1), even if there was no actual rows returned?
        if (empty($rows[0])) {
            throw new DatabaseException("Image not found", 404);
        }

        return $rows[0];
    }

    /**
     * @inheritDoc
     */
    public function getLastModified(array $users, $imageIdentifier = null) {
        $args = [];
        $usersCQL = [];

        foreach ($users as $user) {
            $args[] = $user;
            $usersCQL[] = 'user = ?';
        }

        $where = '(' . join('OR', $usersCQL) . ')';

        if ($imageIdentifier) {
            $where .= ' AND imageIdentifier = ?';
            $args[] = $imageIdentifier;

            $statement = new SimpleStatement("SELECT updated FROM imageinfo WHERE " . $where);
            $rows = $this->execute($statement, $args);

            // see note in getImageProperties
            if (empty($rows[0])) {
                throw new DatabaseException("Image not found", 404);
            }

            $row = $rows[0];
            return new DateTime('@' .  $row['updated'], new DateTimeZone('UTC'));
            //return $row['updated'];
        } else {
            // We'll need to introduce a separate table for this..
            $statement = new SimpleStatement("SELECT last_updated FROM usermeta WHERE " . $where);
            $rows = $this->execute($statement, $args);
            $last_updated = null;

            foreach ($rows as $row) {
                if (!$last_updated || $last_updated < $row['last_updated']) {
                    $last_updated = $row['last_updated'];
                }
            }

            if (!$last_updated) {
                return new DateTime('now', new DateTimeZone('UTC'));
            }

            return $last_updated->toDateTime();
        }
    }

    /**
     * @inheritDoc
     */
    public function getNumImages($user = null) {
        $args = [];
        $cql = "
            SELECT 
                COUNT(imageIdentifier) AS image_count 
            FROM 
                imageinfo";

        if ($user) {
            $args['user'] = $user;
            $cql .= ' WHERE user = ?';
        }

        $statement = $this->session->prepare($cql);

        $rows = $this->execute($statement, $args);
        return (int) $rows[0]['image_count'];
    }

    /**
     * @inheritDoc
     */
    public function getNumBytes($user = null) {
        $args = [];
        $cql = "
            SELECT
                SUM(size) AS image_size
            FROM
                imageinfo
        ";

        if ($user) {
            $args['user'] = $user;
            $cql .= ' WHERE user = ?';
        }

        $statement = $this->session->prepare($cql);

        $rows = $this->execute($statement, $args);
        return (int) $rows[0]['image_size'];
    }

    /**
     * @inheritDoc
     */
    public function getNumUsers() {
        $cql = "
            SELECT
                COUNT(user) AS user_count
            FROM
                imageinfo
        ";

        $statement = $this->session->prepare($cql);
        $rows = $this->execute($statement);
        return (int) $rows[0]['user_count'];
    }

    /**
     * @inheritDoc
     */
    public function getStatus() {
        try {
            $this->cassandra->execute(new SimpleStatement("SELECT NOW() FROM system.local"));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getImageMimeType($user, $imageIdentifier) {
        // TODO: Implement getImageMimeType() method.
    }

    /**
     * @inheritDoc
     */
    public function imageExists($user, $imageIdentifier) {
        try {
            $this->getImageProperties($user, $imageIdentifier);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function insertShortUrl($shortUrlId, $user, $imageIdentifier, $extension = null, array $query = []) {
        // TODO: Implement insertShortUrl() method.
    }

    /**
     * @inheritDoc
     */
    public function getShortUrlId($user, $imageIdentifier, $extension = null, array $query = []) {
        // TODO: Implement getShortUrlId() method.
    }

    /**
     * @inheritDoc
     */
    public function getShortUrlParams($shortUrlId) {
        // TODO: Implement getShortUrlParams() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteShortUrls($user, $imageIdentifier, $shortUrlId = null) {
        // TODO: Implement deleteShortUrls() method.
    }
}