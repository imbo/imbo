<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Exception\DatabaseException;
use Imbo\IdentifierQuoter;
use PDO;
use PDOException;

/**
 * PDO adapter for the image variations
 */
abstract class PDOAdapter implements DatabaseInterface
{
    use IdentifierQuoter;

    private PDO $pdo;

    public const IMAGEVARIATIONS_TABLE = 'imagevariations';

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

    public function storeImageVariationMetadata(string $user, string $imageIdentifier, int $width, int $height): bool
    {
        $sql = <<<SQL
            INSERT INTO {$this->quote(self::IMAGEVARIATIONS_TABLE)} (
                {$this->quote('added')},
                {$this->quote('user')},
                {$this->quote('imageIdentifier')},
                {$this->quote('width')},
                {$this->quote('height')}
            ) VALUES (
                :added,
                :user,
                :imageIdentifier,
                :width,
                :height
            )
        SQL;

        return $this->pdo
            ->prepare($sql)
            ->execute([
                'added'           => time(),
                'user'            => $user,
                'imageIdentifier' => $imageIdentifier,
                'width'           => $width,
                'height'          => $height,
            ]);
    }

    public function getBestMatch(string $user, string $imageIdentifier, int $width): ?array
    {
        $sql = <<<SQL
            SELECT
                {$this->quote('width')},
                {$this->quote('height')}
            FROM
                {$this->quote(self::IMAGEVARIATIONS_TABLE)}
            WHERE
                {$this->quote('user')} = :user
                AND {$this->quote('imageIdentifier')} = :imageIdentifier
                AND {$this->quote('width')} >= :width
            ORDER BY
                {$this->quote('width')} ASC
            LIMIT
                1
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user'            => $user,
            ':imageIdentifier' => $imageIdentifier,
            ':width'           => $width,
        ]);

        /** @var false|array{width:string,height:string} */
        $row = $stmt->fetch();

        if (false === $row) {
            return null;
        }

        return [
            'width' => (int) $row['width'],
            'height' => (int) $row['height'],
        ];
    }

    public function deleteImageVariations(string $user, string $imageIdentifier, ?int $width = null): bool
    {
        $parameters = [
            ':user' => $user,
            ':imageIdentifier' => $imageIdentifier,
        ];

        $widthWhereClause = '';

        if (null !== $width) {
            $widthWhereClause = "AND {$this->quote('width')} = :width";
            $parameters[':width'] = $width;
        }

        $sql = <<<SQL
            DELETE FROM
                {$this->quote(self::IMAGEVARIATIONS_TABLE)}
            WHERE
                {$this->quote('user')} = :user
                AND {$this->quote('imageIdentifier')} = :imageIdentifier
                {$widthWhereClause}
        SQL;

        return $this->pdo
            ->prepare($sql)
            ->execute($parameters);
    }
}
