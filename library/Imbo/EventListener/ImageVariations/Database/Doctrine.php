<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\ImageVariations\Database;

use Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Connection,
    PDO;

/**
 * Doctrine 2 database driver for the image variations
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
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
     * @param Connection $connection Optional connection instance
     */
    public function __construct(array $params, Connection $connection = null) {
        $this->params = array_merge($this->params, $params);

        if (isset($this->params['pdo'])) {
            trigger_error(
                sprintf(
                    'The usage of pdo in the configuration array for %s is deprecated and will be removed in Imbo-3.x',
                    __CLASS__
                ),
                E_USER_DEPRECATED
            );
        }

        if ($connection !== null) {
            trigger_error(
                sprintf(
                    'Specifying a connection instance in %s is deprecated and will be removed in Imbo-3.x',
                    __CLASS__
                ),
                E_USER_DEPRECATED
            );
            $this->setConnection($connection);
        }
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
}
