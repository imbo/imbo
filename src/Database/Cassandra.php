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
    Cassandra\Map,
    Cassandra\Type,
    Cassandra\Timestamp,

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
    protected $metadataNamespaceSeparator = ':::';

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

        $this->execute($statement, ['last_updated' => new Timestamp(), 'user' => $user]);

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
        $statement = $this->session->prepare("
            UPDATE
                imageinfo
            SET
                metadata = ?
            WHERE
                user = ? AND 
                imageIdentifier = ?
        ");

        return (boolean) $this->execute($statement, [
            'metadata' => $this->mappifyMetdata($metadata),
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);
    }

    protected function mappifyMetdata(array $metadata) {
        $metadataMap = new Map(Type::text(), Type::text());

        $normalizedMetadata = [];
        $this->normalizeMetadata($metadata, $normalizedMetadata);

        foreach ($normalizedMetadata as $key => $value) {
            $metadataMap->set($key, (string) $value);
        }

        return $metadataMap;
    }

    protected function demappifyMetadata($metadataMap) {
        $arr = [];

        foreach ($metadataMap as $key => $value) {
            $arr[$key] = $value;
        }

        return $this->denormalizeMetadata($arr);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($user, $imageIdentifier) {
        $statement = $this->session->prepare("
            SELECT
                metadata
            FROM
                imageinfo
            WHERE
                user = ? AND 
                imageIdentifier = ?
        ");

        $rows = $this->execute($statement, [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);

        if (empty($rows[0])) {
            throw new DatabaseException("Image not found", 404);
        }

        if (!$rows[0]['metadata']) {
            return [];
        }

        return $this->demappifyMetadata($rows[0]['metadata']);
    }

    /**
     * @inheritDoc
     */
    public function deleteMetadata($user, $imageIdentifier) {
        if (!$row = $this->getImageProperties($user, $imageIdentifier)) {
            throw new DatabaseException("Image not found", 404);
        }

        $statement = $this->session->prepare("
            UPDATE
                imageinfo
            SET
                metadata = {}
            WHERE
                user = ? AND 
                imageIdentifier = ?
        ");

        return (boolean) $this->execute($statement, [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getImages(array $users, Query $query, Images $model) {
        throw new DatabaseException("Database adapter does not support querying /images.", 501);
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

        if (!$rows->count()) {
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
            $this->session->execute(new SimpleStatement("SELECT NOW() FROM system.local"));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getImageMimeType($user, $imageIdentifier) {
        $props = $this->getImageProperties($user, $imageIdentifier);

        return $props['mime'];
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
        // Cassandra doesn't like null values, so we replace them with the empty string instead.
        if (!$extension) {
            $extension = '';
        }

        $statement = $this->session->prepare("
            INSERT INTO
                shorturl
                (
                    shortUrlId,
                    extension,
                    query,
                    user,
                    imageIdentifier
                )
            VALUES
            (
                :shortUrlId,
                :extension,
                :query,
                :user,
                :imageIdentifier
            )
        ");

        $result = (boolean) $this->execute($statement, [
            'shortUrlId' => $shortUrlId,
            'extension' => $extension,
            'query' => serialize($query),
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);

        $statement = $this->session->prepare("
            INSERT INTO
                shorturl_user
                (
                    user,
                    imageIdentifier,
                    shortUrlId
                )
            VALUES
            (
                :user,
                :imageIdentifier,
                :shortUrlId
            )
        ");

        $resultUser = (boolean) $this->execute($statement, [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
            'shortUrlId' => $shortUrlId,
        ]);

        return $result && $resultUser;
    }

    protected function getShortUrlIdsForUserAndImageIdentifier($user, $imageIdentifier) {
        $statement = $this->session->prepare("
            SELECT
                shorturlid
            FROM
                shorturl_user
            WHERE
                user = :user AND 
                imageIdentifier = :imageIdentifier
        ");

        $rows = $this->execute($statement, [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);

        if (!$rows->count()) {
            return null;
        }

        $ids = [];

        foreach ($rows as $row) {
            $ids[] = $row['shorturlid'];
        }

        return $ids;
    }

    protected function getPlaceholderString($elements) {
        return '(' . substr(str_repeat('?, ', $elements), 0, -2) . ')';
    }

    /**
     * @inheritDoc
     */
    public function getShortUrlId($user, $imageIdentifier, $extension = null, array $query = []) {
        // Cassandra doesn't like to search for null values, so we replace them with the empty string instead.
        if (!$extension) {
            $extension = '';
        }

        $args = $this->getShortUrlIdsForUserAndImageIdentifier($user, $imageIdentifier);
        $rowCount = count($args);

        $args[] = $extension;
        $args[] = serialize($query);

        // We might want to rewrite this to a parallel query later
        $cql = "
            SELECT
                shortUrlId
            FROM
                shorturl
            WHERE
                shortUrlId IN " . $this->getPlaceholderString($rowCount) . " AND
                extension = ? AND 
                query = ?
        ";

        $statement = $this->session->prepare($cql);

        $rows = $this->execute($statement, $args);

        if (!$rows->count()) {
            return null;
        }

        return $rows[0]['shorturlid'];
    }

    /**
     * @inheritDoc
     */
    public function getShortUrlParams($shortUrlId) {
        $statement = $this->session->prepare("
            SELECT
                *
            FROM
                shorturl
            WHERE
                shortUrlId = ?
        ");

        $rows = $this->execute($statement, [$shortUrlId]);

        if (!$rows->count()) {
            return null;
        }

        $row = (array) $rows[0];

        if (!$row['extension']) {
            $row['extension'] = null;
        }

        $row['imageIdentifier'] = $row['imageidentifier'];
        $row['query'] = unserialize($row['query']);
        unset($row['imageidentifier']);

        return $row;
    }

    /**
     * @inheritDoc
     */
    public function deleteShortUrls($user, $imageIdentifier, $shortUrlId = null) {
        if (!$shortUrlId) {
            $shortUrlIds = $this->getShortUrlIdsForUserAndImageIdentifier($user, $imageIdentifier);
        } else {
            $shortUrlIds = [$shortUrlId];
        }

        $cql = "
            DELETE FROM 
                shorturl_user
            WHERE
                user = :user AND 
                imageidentifier = :imageidentifier
        ";

        $args = [
            'user' => $user,
            'imageidentifier' => $imageIdentifier,
        ];

        if ($shortUrlId) {
            $cql .= ' AND shorturlid = :shorturlid';
            $args['shorturlid'] = $shortUrlId;
        }

        $deleteUser = (boolean) $this->execute(new SimpleStatement($cql), $args);
        $deleteShortUrls = true;

        if ($shortUrlIds) {
            $statement = new SimpleStatement("
                DELETE FROM
                    shorturl
                WHERE
                    shortUrlId IN " . $this->getPlaceholderString(count($shortUrlIds)) . "
            ");

            $deleteShortUrls = (boolean) $this->execute($statement, $shortUrlIds);
        }

        return $deleteUser && $deleteShortUrls;
    }

    /**
     * Normalize metadata
     *
     * @param array $metadata Metadata
     * @param array $normalized Normalized metadata
     * @param string $namespace Namespace for keys
     * @return array Returns an associative array with only one level
     */
    private function normalizeMetadata(array &$metadata, array &$normalized, $namespace = '') {
        foreach ($metadata as $key => $value) {
            if (strstr($key, $this->metadataNamespaceSeparator) !== false) {
                throw new DatabaseException('Invalid metadata', 400);
            }

            $ns = $namespace . ($namespace ? $this->metadataNamespaceSeparator : '') . $key;

            if (is_array($value)) {
                $this->normalizeMetadata($value, $normalized, $ns);
            } else {
                $normalized[$ns] = $value;
            }
        }
    }

    /**
     * De-normalize metadata
     *
     * @param array $data Metadata
     * @return array
     */
    private function denormalizeMetadata(array $data) {
        $result = [];

        foreach ($data as $key => $value)
        {
            $keys = explode($this->metadataNamespaceSeparator, $key);
            $tmp = &$result;

            foreach ($keys as $i => $key)
            {
                if (!isset($tmp[$key]))
                {
                    $tmp[$key] = null;
                }

                $tmp = &$tmp[$key];
            }

            $tmp = $value;
        }

        return $result;
    }
}