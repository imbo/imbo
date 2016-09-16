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
 * Valid parameters for this driver:
 *
 * - <pre>(string) dbname</pre> Name of the database to connect to
 * - <pre>(string) user</pre> Username to use when connecting
 * - <pre>(string) password</pre> Password to use when connecting
 * - <pre>(string) host</pre> Hostname to use when connecting
 * - <pre>(string) driver</pre> Which driver to use
 * - <pre>(PDO) pdo</pre> PDO adapter to use, as an alternative to specifying the above
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
        'dbname'    => null,
        'user'      => null,
        'password'  => null,
        'host'      => null,
        'driver'    => null,
        'pdo'       => null,
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
    public function __construct(array $params = null, Connection $connection = null) {
        if ($params !== null) {
            $this->params = array_merge($this->params, $params);
        }

        if ($connection !== null) {
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
