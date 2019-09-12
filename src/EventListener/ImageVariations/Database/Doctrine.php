<?php
namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Exception\InvalidArgumentException,
    Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Connection,
    PDO;

/**
 * Doctrine 2 database driver for the image variations
 *
 * Refer to http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest for configuration parameters
 *
 * @package Database
 */
class Doctrine implements DatabaseInterface {
    /**
     * Parameters for the Doctrine connection
     *
     * @var array
     */
    private $params = [
        'tableName' => 'imagevariations',
    ];

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
     * @throws InvalidArgumentException
     */
    public function __construct(array $params) {
        if (isset($params['pdo'])) {
            throw new InvalidArgumentException(sprintf(
                "The usage of 'pdo' in the configuration for %s is not allowed, use 'driver' instead",
                __CLASS__
            ), 500);
        }

        $this->params = array_merge($this->params, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function storeImageVariationMetadata($user, $imageIdentifier, $width, $height) {
        return (boolean) $this->getConnection()->insert($this->params['tableName'], [
            'added'           => time(),
            'user'            => $user,
            'imageIdentifier' => $imageIdentifier,
            'width'           => $width,
            'height'          => $height,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBestMatch($user, $imageIdentifier, $width) {
        $qb = $this->getConnection()->createQueryBuilder();
        $qb->select('width', 'height')
           ->from($this->params['tableName'], 'iv')
           ->where('iv.user = :user')
           ->andWhere('iv.imageIdentifier = :imageIdentifier')
           ->andWhere('iv.width >= :width')
           ->setMaxResults(1)
           ->orderBy('iv.width', 'ASC')
           ->setParameters([
               ':user'            => $user,
               ':imageIdentifier' => $imageIdentifier,
               ':width'           => $width,
           ]);

        $stmt = $qb->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? array_map('intval', $row) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImageVariations($user, $imageIdentifier, $width = null) {
        $qb = $this->getConnection()->createQueryBuilder();

        $qb->delete($this->params['tableName'])
           ->where('user = :user')
           ->andWhere('imageIdentifier = :imageIdentifier')
           ->setParameters([
               ':user' => $user,
               ':imageIdentifier' => $imageIdentifier,
           ]);

        if ($width) {
            $qb->andWhere('width = :width')
               ->setParameter(':width', $width);
        }

        return (boolean) $qb->execute();
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
}
