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
                $pdo = new \PDO($this->params['dsn'], $this->params['username'], $this->params['password'], $this->params['driverOptions']);
            }
            catch (PDOException $e)
            {
                // We try to avoid leaking information about the database connection information into the exception
                throw new DatabaseException('Failed setting up database connection: ' . $e->getErrorCode(), 500);
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
            if ($this->imageExists($imageIdentifier)) {
                throw new DatabaseException('Image already exists', 400);
            }

            $insertImageStatement = $this->pdo->prepare("
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
            $insertImageStatement->closeCursor();
            
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
            
            $deleteImageStatement->closeCursor();
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
            
            $insertMetadataStatement->closeCursor();
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to edit image data: ' . $e->getMessage(), 500, $e);
        }

        return true;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::getMetadata()
     */
    public function getMetadata($imageIdentifier) {
        $metadata = array();
    
        try {
            $getMetadataStatement = $this->pdo->prepare("
                SELECT
                    m.field, m.value
                FROM
                    image_metadata m
                WHERE
                    m.imageIdentifier = :imageIdentifier
            ");
            
            $getMetadataStatement->execute(array(
                ':imageIdentifier' => $imageIdentifier,
            ));
            
            $rows = $getMetadataStatement->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($rows as $row)
            {
                $metadata[$row['field']] = $row['value'];            
            }
            
            $getMetadataStatement->closeCursor();
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to fetch image metadata: ' . $e->getMessage(), 500, $e);
        }
        
        return $metadata;
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
            
            $deleteImageMetadataStatement->closeCursor();
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to remove metadata', 500, $e);
        }

        return true;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::getImages()
     */
    public function getImages(Query $query) {
        try {
        
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to search for images: ' . $e->getMessage(), 500, $e);
        }

        return $images;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::load()
     */
    public function load($imageIdentifier, Image $image) {
        try {
            $loadImageStatement = $this->pdo->prepare("
                SELECT
                    i.name, i.size, i.width, i.height
                FROM
                    image
                WHERE
                    imageIdentifier = :imageIdentifier
            ");
            
            $loadImageStatement->execute(array(
                ':imageIdentifier' => $imageIdentifier,
            ));
            
            $data = $loadImageStatement->fetch(PDO::FETCH_ASSOC);
            $loadImageStatement->closeCursor();
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to fetch image data: ' . $e->getMessage(), 500, $e);
        }

        $image->setFilename($data['name'])
              ->setFilesize($data['size'])
              ->setWidth($data['width'])
              ->setHeight($data['height']);

        return true;
    }
    
    protected function imageExists($imageIdentifier)
    {
        try
        {
            $imageExistsStatement = $this->pdo->prepare("
                SELECT
                    1
                FROM
                    image
                WHERE
                    imageIdentifier = :imageIdentifier
            ");
            
            $imageExistsStatement->execute(array(
                ':imageIdentifier' => $imageIdentifier,
            ));
            
            $rowTest = $imageExistsStatement->fetch();
            
            $imageExistsStatement->closeCursor();
            
            return !empty($rowTest);
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to test for existance of image: ' . $e->getMessage(), 500, $e);
        }
    }
}
