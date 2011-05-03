<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Mats Lindh <mats@lindh.no>
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
 * @package PHPIMS
 * @subpackage DatabaseDriver
 * @author Mats Lindh <mats@lindh.no>
 * @copyright Copyright (c) 2011, Mats Lindh
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Database\Driver;

use PHPIMS\Database\Exception as DatabaseException;
use PHPIMS\Database\DriverInterface;
use PHPIMS\Image;
use PHPIMS\Operation\GetImages\Query;

/**
 * MySQL database driver
 *
 * A MySQL (PDO-based) database driver for PHPIMS
 * Might also work with other PDO-based drivers; provide your driver in the DSN.
 *
 * Valid parameters for this driver:
 *
 * - <pre>(string) dsn</pre> The DSN of the database server to connect to. See http://no.php.net/manual/en/pdo.construct.php for examples.
 * - <pre>(string) username</pre> Username for the database connection. Optional for a few PDO drivers (if the DSN provides it).
 * - <pre>(string) password</pre> Password for access to the database with the provided username. Optional for a few PDO drivers (if the DSN provides it).
 * - <pre>(string) driverOptions</pre> Specific options for the driver provided in the DSN. See http://no.php.net/manual/en/pdo.construct.php for examples.
 *
 * @package PHPIMS
 * @subpackage DatabaseDriver
 * @author Mats Lindh <mats@lindh.no>
 * @copyright Copyright (c) 2011, Mats Lindh
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class MySQL implements DriverInterface {
    /**
     * The pdo instance used by the driver
     *
     * @var PDO
     */
    private $pdo = null;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = array(
        'dsn'       => 'mysql:dbname=phpims',
        'username'  => null,
        'password'  => null,
        'driverOptions' => array(),
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param PDO $pdo Existing PDO connection if you want to reuse an existing connection
     */
    public function __construct(array $params = null, $pdo = null) {
        if ($params !== null) {
            $this->params = array_merge($this->params, $params);
        }

        if ($pdo === null) {
            // @codeCoverageIgnoreStart
            try
            {
                $pdo        = new \PDO($this->params['dsn'], $this->params['username'], $this->params['password'], $this->params['driverOptions']);
            }
            catch (PDOException $e)
            {
                // We try to avoid leaking information about the database connection information into the exception
                throw new DatabaseException('Failed setting up database connection: ' $e->getErrorCode(), 500);
            }
        }
        // @codeCoverageIgnoreEnd

        $this->pdo = $pdo;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::insertImage()
     */
    public function insertImage($imageIdentifier, Image $image) {
        try {
            // See if the image already exists
            if ($this->imageExists($imageIdentifier))
                throw new DatabaseException('Image already exists', 400);
            }

            $imageStatement = $this->pdo->prepare("
                INSERT INTO
                    image
                    (name, size, imageIdentifier, mime, added, width, height)
                VALUES
                (
                    :name,
                    :size,
                    :imageIdentifier,
                    :mime,
                    :added,
                    :width,
                    :height                
                )
            ");
            
            $insertImageStatement->execute(array(
                ':name'            => $image->getFilename(),
                ':size'            => $image->getFilesize(),
                ':imageIdentifier' => $imageIdentifier,
                ':mime'            => $image->getMimeType(),
                ':added'           => time(),
                ':width'           => $image->getWidth(),
                ':height'          => $image->getHeight(),
            ));
        } catch (\PDOException $e) {
            $pdo->rollback();
            throw new DatabaseException('Unable to save image data', 500, $e);
        }

        return true;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::deleteImage()
     */
    public function deleteImage($imageIdentifier) {
        try {
            $deleteImageStatement = $this->pdo->prepare("
                DELETE FROM
                    image
                WHERE
                    imageIdentifier = :imageIdentifier
            ");
            
            $deleteImageStatement->execute(array(
                ':imageIdentifier' => $imageIdentifier,
            ));
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to delete image data: ' . $e->getMessage(), 500, $e);
        }

        return true;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::editMetadata()
     */
    public function updateMetadata($imageIdentifier, array $metadata) {
        try {
            $this->pdo->beginTransaction();
            
            $this->deleteMetadata($imageIdentifier);
        
            $insertMetadataStatement = $this->pdo->prepare("
                INSERT INTO
                    image_metadata
                    (imageIdentifier, field, value)
                VALUES
                
            ");
            
            $insertMetadataStatement->execute();
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to edit image data: ' . $e->getMessage(), 500, $e);
        }

        return true;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::getMetadata()
     */
    public function getMetadata($imageIdentifier) {
        try {
            $data = $this->collection->findOne(array('imageIdentifier' => $imageIdentifier));
        } catch (\MongoException $e) {
            throw new DatabaseException('Unable to fetch image metadata', 500, $e);
        }

        return isset($data['metadata']) ? $data['metadata'] : array();
    }

    /**
     * @see PHPIMS\Database\DriverInterface::deleteMetadata()
     */
    public function deleteMetadata($imageIdentifier) {
        try {
            $deleteImageMetadataStatement = $this->pdo->prepare("
                DELETE FROM
                    image_metadata
                WHERE
                    imageIdentifier = :imageIdentifier
            ");
            
            $deleteImageMetadataStatement->execute(array(
                ':imageIdentifier' => $imageIdentifier,
            ));
        } catch (DatabaseException $e) {
            throw new DatabaseException('Unable to remove metadata', 500, $e);
        }

        return true;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::getImages()
     */
    public function getImages(Query $query) {
        // Initialize return value
        $images = array();

        // Query data
        $queryData = array();

        $from = $query->from();
        $to = $query->to();

        if ($from || $to) {
            $tmp = array();

            if ($from !== null) {
                $tmp['$gt'] = $from;
            }

            if ($to !== null) {
                $tmp['$lt'] = $to;
            }

            $queryData['added'] = $tmp;
        }

        $metadataQuery = $query->query();

        if (!empty($metadataQuery)) {
            $queryData['metadata'] = $metadataQuery;
        }

        // Fields to fetch
        $fields = array('added', 'imageIdentifier', 'mime', 'name', 'size', 'width', 'height');

        if ($query->returnMetadata()) {
            $fields[] = 'metadata';
        }

        try {
            $cursor = $this->collection->find($queryData, $fields)
                                       ->limit($query->num())
                                       ->sort(array('added' => -1));

            // Skip some images if a page has been set
            if (($page = $query->page()) > 1) {
                $skip = $query->num() * ($page - 1);
                $cursor->skip($skip);
            }

            foreach ($cursor as $image) {
                unset($image['_id']);
                $images[] = $image;
            }
        } catch (\MongoException $e) {
            throw new DatabaseException('Unable to search for images', 500, $e);
        }

        return $images;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::load()
     */
    public function load($imageIdentifier, Image $image) {
        try {
            $fields = array('name', 'size', 'width', 'height');
            $data = $this->collection->findOne(array('imageIdentifier' => $imageIdentifier), $fields);
        } catch (\MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        $image->setFilename($data['name'])
              ->setFilesize($data['size'])
              ->setWidth($data['width'])
              ->setHeight($data['height']);

        return true;
    }
}
