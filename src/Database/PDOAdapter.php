<?php declare(strict_types=1);
namespace Imbo\Database;

use DateTime;
use DateTimeZone;
use Imbo\Exception\DatabaseException;
use Imbo\Exception\DuplicateImageIdentifierException;
use Imbo\IdentifierQuoter;
use Imbo\Model\Image;
use Imbo\Model\Images;
use Imbo\Resource\Images\Query;
use PDO;
use PDOException;

/**
 * PDO database driver
 */
abstract class PDOAdapter implements DatabaseInterface
{
    use IdentifierQuoter;

    private PDO $pdo;

    public const IMAGEINFO_TABLE = 'imageinfo';
    public const SHORTURL_TABLE = 'shorturl';

    abstract protected function getUniqueConstraintExceptionCode(): int;

    /**
     * @return array<int,bool|int>
     */
    protected function getPDOOptions(): array
    {
        return [
            PDO::ATTR_PERSISTENT => true,
        ];
    }

    /**
     * Class constructor
     *
     * @param string $dsn Database DSN
     * @param string $username Username for the DSN string
     * @param string $password Password for the DSN string
     * @param array<mixed> $options Driver specific options
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, array $options = [])
    {
        try {
            $this->pdo = new PDO(
                $dsn,
                $username,
                $password,
                array_replace(
                    // Default options
                    $this->getPDOOptions(),

                    // User specified options
                    $options,

                    // Forced options
                    [
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    ],
                ),
            );
        } catch (PDOException $e) {
            throw new DatabaseException('Unable to connect to the database', 503, $e);
        }
    }

    public function insertImage(string $user, string $imageIdentifier, Image $image, bool $updateIfDuplicate = true): bool
    {
        $now = time();

        if ($added = $image->getAddedDate()) {
            $added = $added->getTimestamp();
        }

        if ($updated = $image->getUpdatedDate()) {
            $updated = $updated->getTimestamp();
        }

        if ($updateIfDuplicate && $id = $this->getImageId($user, $imageIdentifier)) {
            $sql = <<<SQL
                UPDATE
                    {$this->quote(self::IMAGEINFO_TABLE)}
                SET
                    {$this->quote('updated')} = :updated
                WHERE
                    {$this->quote('id')} = :id
            SQL;

            return $this->pdo
                ->prepare($sql)
                ->execute([
                    ':id' => $id,
                    ':updated' => $now,
                ]);
        }

        $sql = <<<SQL
            INSERT INTO {$this->quote(self::IMAGEINFO_TABLE)} (
                {$this->quote('user')},
                {$this->quote('imageIdentifier')},
                {$this->quote('size')},
                {$this->quote('extension')},
                {$this->quote('mime')},
                {$this->quote('added')},
                {$this->quote('updated')},
                {$this->quote('width')},
                {$this->quote('height')},
                {$this->quote('checksum')},
                {$this->quote('originalChecksum')}
            ) VALUES (
                :user,
                :imageIdentifier,
                :size,
                :extension,
                :mime,
                :added,
                :updated,
                :width,
                :height,
                :checksum,
                :originalChecksum
            )
        SQL;

        try {
            return $this->pdo
                ->prepare($sql)
                ->execute([
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
                ]);
        } catch (PDOException $e) {
            if ($this->getUniqueConstraintExceptionCode() === (int) $e->getCode()) {
                throw new DuplicateImageIdentifierException(
                    'Duplicate image identifier when attempting to insert image into DB.',
                    503,
                    $e,
                );
            }

            throw new DatabaseException('Unable to save image data', 500, $e);
        }
    }

    public function deleteImage(string $user, string $imageIdentifier): bool
    {
        if (!$id = $this->getImageId($user, $imageIdentifier)) {
            throw new DatabaseException('Image not found', 404);
        }

        $sql = <<<SQL
            DELETE FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            WHERE
                {$this->quote('id')} = :id
        SQL;

        return $this->pdo
            ->prepare($sql)
            ->execute(['id' => $id]);
    }

    public function updateMetadata(string $user, string $imageIdentifier, array $metadata): bool
    {
        $imageId = $this->getImageId($user, $imageIdentifier);

        $existing = $this->getMetadata($user, $imageIdentifier);
        $metadata = array_merge($existing, $metadata);

        $sql = <<<SQL
            UPDATE
                {$this->quote(self::IMAGEINFO_TABLE)}
            SET
                {$this->quote('metadata')} = :metadata
            WHERE
                {$this->quote('id')} = :id
        SQL;

        return $this->pdo->prepare($sql)->execute([
            ':id' => $imageId,
            ':metadata' => json_encode($metadata),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function getMetadata(string $user, string $imageIdentifier): array
    {
        if (!$id = $this->getImageId($user, $imageIdentifier)) {
            throw new DatabaseException('Image not found', 404);
        }

        $sql = <<<SQL
            SELECT
                {$this->quote('metadata')}
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            WHERE
                {$this->quote('id')} = :id
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
        ]);

        /** @var mixed */
        $result = $stmt->fetchColumn();

        /** @var array<string,mixed> */
        return null === $result ? [] : json_decode((string) $result, true);
    }

    public function deleteMetadata($user, $imageIdentifier)
    {
        if (!$id = $this->getImageId($user, $imageIdentifier)) {
            throw new DatabaseException('Image not found', 404);
        }

        $sql = <<<SQL
            UPDATE
                {$this->quote(self::IMAGEINFO_TABLE)}
            SET
                {$this->quote('metadata')} = null
            WHERE
                {$this->quote('id')} = :id
        SQL;

        return $this->pdo->prepare($sql)->execute([
            ':id' => $id,
        ]);
    }

    public function getImages(array $users, Query $query, Images $model): array
    {
        $images     = [];
        $where      = [];
        $parameters = [];
        $orderBy    = [];

        if ($users) {
            $userWhere = [];

            foreach ($users as $i => $user) {
                $parameterName = ':user' . $i;
                $userWhere[] = "{$this->quote('user')} = " . $parameterName;
                $parameters[$parameterName] = $user;
            }

            $where[] = '(' . implode(' OR ', $userWhere) . ')';
        }

        if ($sort = $query->getSort()) {
            $validFields = [
                'size'             => true,
                'user'             => true,
                'imageIdentifier'  => true,
                'extension'        => true,
                'mime'             => true,
                'added'            => true,
                'updated'          => true,
                'width'            => true,
                'height'           => true,
                'checksum'         => true,
                'originalChecksum' => true,
            ];

            foreach ($sort as $f) {
                if (!isset($validFields[$f['field']])) {
                    throw new DatabaseException('Invalid sort field: ' . $f['field'], 400);
                }

                $orderBy[$f['field']] = $f['sort'];
            }
        } else {
            $orderBy['added'] = 'DESC';
        }

        $from = $query->getFrom();
        $to = $query->getTo();

        if ($from || $to) {
            if ($from !== null) {
                $where[] = "{$this->quote('added')} >= :from";
                $parameters[':from'] = $from;
            }

            if ($to !== null) {
                $where[] = "{$this->quote('added')} <= :to";
                $parameters[':to'] = $to;
            }
        }

        if ($imageIdentifiers = $query->getImageIdentifiers()) {
            $imageIdentifiersWhere = [];

            foreach ($imageIdentifiers as $i => $id) {
                $parameterName = ':imageIdentifier' . $i;
                $imageIdentifiersWhere[] = "{$this->quote('imageIdentifier')} = " . $parameterName;
                $parameters[$parameterName] = $id;
            }

            $where[] = '(' . implode(' OR ', $imageIdentifiersWhere) . ')';
        }

        if ($checksums = $query->getChecksums()) {
            $checksumsWhere = [];

            foreach ($checksums as $i => $checksum) {
                $parameterName = ':checksum' . $i;
                $checksumsWhere[] = "{$this->quote('checksum')} = " . $parameterName;
                $parameters[$parameterName] = $checksum;
            }

            $where[] = '(' . implode(' OR ', $checksumsWhere) . ')';
        }

        if ($originalChecksums = $query->getOriginalChecksums()) {
            $originalChecksumsWhere = [];

            foreach ($originalChecksums as $i => $originalChecksum) {
                $parameterName = ':originalChecksum' . $i;
                $originalChecksumsWhere[] = "{$this->quote('originalChecksum')} = " . $parameterName;
                $parameters[$parameterName] = $originalChecksum;
            }

            $where[] = '(' . implode(' OR ', $originalChecksumsWhere) . ')';
        }

        if (!empty($where)) {
            $where = 'WHERE ' . implode(' AND ', $where);
        } else {
            $where = '';
        }

        $hitsSql = <<<SQL
            SELECT
                COUNT({$this->quote('id')})
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            {$where}
        SQL;

        $stmt = $this->pdo->prepare($hitsSql);
        $stmt->execute($parameters);
        $model->setHits((int) $stmt->fetchColumn());

        $limitClause = '';

        if ($limit = $query->getLimit()) {
            $limitClause = 'LIMIT ' . $limit;
        }

        if ($page = $query->getPage()) {
            if ('' === $limitClause) {
                throw new DatabaseException('page is not allowed without limit', 400);
            }

            $offset = $query->getLimit() * ($page - 1);

            if (0 < $offset) {
                $limitClause .= ' OFFSET ' . $offset;
            }
        }

        $orderByClause = 'ORDER BY ' . implode(', ', array_map(function (string $col, string $dir): string {
            return sprintf("{$this->quote($col)} %s", $dir);
        }, array_keys($orderBy), array_values($orderBy)));

        $sql = <<<SQL
            SELECT
                *
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            {$where}
            {$orderByClause}
            {$limitClause}
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
        /** @var array<int,array{metadata:?string,extension:string,added:string,updated:string,checksum:string,originalChecksum:?string,user:string,imageIdentifier:string,mime:string,size:string,width:string,height:string}> */
        $rows = $stmt->fetchAll();

        $returnMetadata = $query->getReturnMetadata();

        foreach ($rows as $row) {
            $image = [
                'extension'        => $row['extension'],
                'added'            => new DateTime('@' . (int) $row['added'], new DateTimeZone('UTC')),
                'updated'          => new DateTime('@' . (int) $row['updated'], new DateTimeZone('UTC')),
                'checksum'         => $row['checksum'],
                'originalChecksum' => isset($row['originalChecksum']) ? $row['originalChecksum'] : null,
                'user'             => $row['user'],
                'imageIdentifier'  => $row['imageIdentifier'],
                'mime'             => $row['mime'],
                'size'             => (int) $row['size'],
                'width'            => (int) $row['width'],
                'height'           => (int) $row['height'],
            ];

            if ($returnMetadata) {
                /** @var array<string,mixed> $image['metadata'] */
                $image['metadata'] = null !== $row['metadata'] ? json_decode($row['metadata'], true) : [];
            }

            $images[] = $image;
        }

        return $images;
    }

    public function getImageProperties(string $user, string $imageIdentifier): array
    {
        $sql = <<<SQL
            SELECT
                *
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            WHERE
                {$this->quote('user')} = :user
                AND {$this->quote('imageIdentifier')} = :imageIdentifier
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user'            => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);

        /** @var false|array<string,string> */
        $row = $stmt->fetch();

        if (false === $row) {
            throw new DatabaseException('Image not found', 404);
        }

        return [
            'size'      => (int) $row['size'],
            'width'     => (int) $row['width'],
            'height'    => (int) $row['height'],
            'mime'      => $row['mime'],
            'extension' => $row['extension'],
            'added'     => (int) $row['added'],
            'updated'   => (int) $row['updated'],
        ];
    }

    public function load(string $user, string $imageIdentifier, Image $image): bool
    {
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

    public function getLastModified(array $users, ?string $imageIdentifier = null): DateTime
    {
        $where = [];
        $parameters = [];

        if (count($users)) {
            $userWhere = [];

            foreach ($users as $i => $user) {
                $parameterName = ':user' . $i;
                $userWhere[] = "{$this->quote('user')} = " . $parameterName;
                $parameters[$parameterName] = $user;
            }

            $where[] = '(' . implode(' OR ', $userWhere) . ')';
        }

        if ($imageIdentifier !== null) {
            $where[] = "({$this->quote('imageIdentifier')} = :imageIdentifier)";
            $parameters[':imageIdentifier'] = $imageIdentifier;
        }

        $whereClause = '';

        if (count($where)) {
            $whereClause = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = <<<SQL
            SELECT
                {$this->quote('updated')}
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            {$whereClause}
            ORDER BY
                {$this->quote('updated')} DESC
            LIMIT
                1
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        /** @var false|string */
        $updated = $stmt->fetchColumn();

        if (false === $updated && null !== $imageIdentifier) {
            throw new DatabaseException('Image not found', 404);
        } elseif (false === $updated) {
            $updated = time();
        }

        return new DateTime('@' . $updated, new DateTimeZone('UTC'));
    }

    public function setLastModifiedNow(string $user, string $imageIdentifier): DateTime
    {
        return $this->setLastModifiedTime($user, $imageIdentifier, new DateTime('@' . time(), new DateTimeZone('UTC')));
    }

    public function setLastModifiedTime(string $user, string $imageIdentifier, DateTime $time): DateTime
    {
        if (!$imageId = $this->getImageId($user, $imageIdentifier)) {
            throw new DatabaseException('Image not found', 404);
        }

        $sql = <<<SQL
            UPDATE
                {$this->quote(self::IMAGEINFO_TABLE)}
            SET
                {$this->quote('updated')} = :updated
            WHERE
                {$this->quote('id')} = :id
        SQL;

        $this->pdo->prepare($sql)->execute([
            ':id' => $imageId,
            ':updated' => $time->getTimestamp(),
        ]);

        return $time;
    }

    public function getNumImages(?string $user = null): int
    {
        $whereClause = '';
        $parameters = [];

        if (null !== $user) {
            $whereClause = "WHERE {$this->quote('user')} = :user";
            $parameters[':user'] = $user;
        }

        $sql = <<<SQL
            SELECT
                COUNT({$this->quote('id')})
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            {$whereClause}
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        return (int) $stmt->fetchColumn();
    }

    public function getNumBytes(?string $user = null): int
    {
        $parameters = [];
        $whereClause = '';

        if (null !== $user) {
            $whereClause = "WHERE {$this->quote('user')} = :user";
            $parameters[':user'] = $user;
        }

        $sql = <<<SQL
            SELECT
                SUM({$this->quote('size')})
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            {$whereClause}
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        return (int) $stmt->fetchColumn();
    }

    public function getNumUsers(): int
    {
        return count($this->getAllUsers());
    }

    public function getStatus(): bool
    {
        return true;
    }

    public function getImageMimeType(string $user, string $imageIdentifier): string
    {
        $sql = <<<SQL
            SELECT
                {$this->quote('mime')}
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            WHERE
                {$this->quote('user')} = :user
                AND {$this->quote('imageIdentifier')} = :imageIdentifier
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user'            => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);

        /** @var false|string */
        $mime = $stmt->fetchColumn();

        if (false === $mime) {
            throw new DatabaseException('Image not found', 404);
        }

        return $mime;
    }

    public function imageExists(string $user, string $imageIdentifier): bool
    {
        return (bool) $this->getImageId($user, $imageIdentifier);
    }

    public function insertShortUrl(string $shortUrlId, string $user, string $imageIdentifier, ?string $extension = null, array $query = []): bool
    {
        $sql = <<<SQL
            INSERT INTO {$this->quote(self::SHORTURL_TABLE)} (
                {$this->quote('shortUrlId')},
                {$this->quote('user')},
                {$this->quote('imageIdentifier')},
                {$this->quote('extension')},
                {$this->quote('query')}
            ) VALUES (
                :shortUrlId,
                :user,
                :imageIdentifier,
                :extension,
                :query
            )
        SQL;
        return $this->pdo->prepare($sql)->execute([
            'shortUrlId'      => $shortUrlId,
            'user'            => $user,
            'imageIdentifier' => $imageIdentifier,
            'extension'       => $extension,
            'query'           => serialize($query),
        ]);
    }

    public function getShortUrlParams(string $shortUrlId): ?array
    {
        $sql = <<<SQL
            SELECT
                {$this->quote('user')},
                {$this->quote('imageIdentifier')},
                {$this->quote('extension')},
                {$this->quote('query')}
            FROM
                {$this->quote(self::SHORTURL_TABLE)}
            WHERE
                {$this->quote('shortUrlId')} = :shortUrlId
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':shortUrlId' => $shortUrlId,
        ]);

        /** @var false|array{user:string,imageIdentifier:string,extension:string,query:string} */
        $row = $stmt->fetch();

        if (false === $row) {
            return null;
        }

        /** @var array<string,string|array<string>> */
        $query = unserialize($row['query']);
        $row['query'] = $query;
        return $row;
    }

    public function getShortUrlId(string $user, string $imageIdentifier, ?string $extension = null, array $query = []): ?string
    {
        $parameters = [
            ':user'            => $user,
            ':imageIdentifier' => $imageIdentifier,
            ':query'           => serialize($query),
        ];

        if ($extension === null) {
            $extensionWhere = "AND {$this->quote('extension')} is NULL";
        } else {
            $extensionWhere = "AND {$this->quote('extension')} = :extension";
            $parameters[':extension'] = $extension;
        }

        $sql = <<<SQL
            SELECT
                {$this->quote('shortUrlId')}
            FROM
                {$this->quote(self::SHORTURL_TABLE)}
            WHERE
                {$this->quote('user')} = :user
                AND {$this->quote('imageIdentifier')} = :imageIdentifier
                AND {$this->quote('query')} = :query
                {$extensionWhere}
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        /** @var false|array<string,string> */
        $row = $stmt->fetch();

        if (false === $row) {
            return null;
        }

        return $row['shortUrlId'];
    }

    public function deleteShortUrls(string $user, string $imageIdentifier, ?string $shortUrlId = null): bool
    {
        $where = [
            "{$this->quote('user')} = :user",
            "{$this->quote('imageIdentifier')} = :imageIdentifier",
        ];
        $parameters = [
            ':user' => $user,
            ':imageIdentifier' => $imageIdentifier,
        ];

        if ($shortUrlId) {
            $where[] = "{$this->quote('shortUrlId')} = :shortUrlId";
            $parameters[':shortUrlId'] = $shortUrlId;
        }

        $whereClause = implode(' AND ', $where);

        $sql = <<<SQL
            DELETE FROM
                {$this->quote(self::SHORTURL_TABLE)}
            WHERE
                {$whereClause}
        SQL;

        return $this->pdo
            ->prepare($sql)
            ->execute($parameters);
    }

    public function getAllUsers(): array
    {
        $sql = <<<SQL
            SELECT
                DISTINCT({$this->quote('user')})
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            ORDER BY
                {$this->quote('user')} ASC
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        /** @var array<string> */
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Get the internal image ID
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return ?int
     */
    protected function getImageId(string $user, string $imageIdentifier): ?int
    {
        $sql = <<<SQL
            SELECT
                {$this->quote('id')}
            FROM
                {$this->quote(self::IMAGEINFO_TABLE)}
            WHERE
                {$this->quote('user')} = :user
                AND {$this->quote('imageIdentifier')} = :imageIdentifier
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user'            => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);

        /** @var false|array<string,string> */
        $row = $stmt->fetch();

        return false === $row ? null : (int) $row['id'];
    }
}
