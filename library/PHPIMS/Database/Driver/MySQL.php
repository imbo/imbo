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
    public function insertImage($publicKey, $imageIdentifier, Image $image) {
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
    public function deleteImage($publicKey, $imageIdentifier) {
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
    public function updateMetadata($publicKey, $imageIdentifier, array $metadata) {
        try {
            $this->pdo->beginTransaction();

            $this->deleteMetadata($imageIdentifier);

            if ($metadata)
            {
                $insertValues = array();

                foreach ($metadata as $field => $value)
                {
                    $insertValues[] = $imageIdentifier;
                    $insertValues[] = $field;
                    $insertValues[] = $value;
                }

                $insertMetadataStatement = $this->pdo->prepare("
                    INSERT INTO
                        image_metadata
                        (imageIdentifier, field, value)
                    VALUES
                        " . self::getInsertValuesString(3, count($metadata)) . "
                ");

                $insertMetadataStatement->execute($insertValues);

                $insertMetadataStatement->closeCursor();
            }

            $this->pdo->commit();
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to edit image data: ' . $e->getMessage(), 500, $e);
        }

        return true;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::getMetadata()
     */
    public function getMetadata($publicKey, $imageIdentifier) {
        return $this->getMetadataForImages(array($imageIdentifier));
    }

    /**
     * @see PHPIMS\Database\DriverInterface::deleteMetadata()
     */
    public function deleteMetadata($publicKey, $imageIdentifier) {
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
    public function getImages($publicKey, Query $query) {
        $images = array();

        $additionalTables = array();
        $whereStatements = array();
        $whereValues = array();

        if ($query->from())
        {
            $whereStatements[] = 'AND i.added > :timeFrom';
            $whereValues[':timeFrom'] = $query->from();
        }

        if ($query->to())
        {
            $whereStatements[] = 'AND i.added < :timeTo';
            $whereValues[':timeTo'] = $query->to();
        }

        if ($query->query()) {
            // we should probably have a better solution than self-joining for each metadata query statement
            $placeholderIndex = 0;

            foreach($query->query() as $field => $value)
            {
                $additionalTables[] = 'image_metadata im' . $placeholderIndex;
                $whereStatements[] = 'AND i.imageIdentifier = im' . $placeholderIndex . '.imageIdentifier';

                $whereStatements[] = 'AND im' . $placeholderIndex . '.field = :metaField_' . $placeholderIndex . ' AND im' . $placeholderIndex . '.value = :metaValue_' . $placeholderIndex;
                $whereValues[':metaField_' . $placeholderIndex] = $field;
                $whereValues[':metaValue_' . $placeholderIndex] = $value;

                $placeholderIndex++;
            }
        }

        try {
            $start = $query->num() * ($query->page() - 1);
            $generatedSQL = '';

            if ($additionalTables)
            {
                $generatedSQL .= ', ' . join(', ', $additionalTables);
            }

            if ($whereStatements)
            {
                $generatedSQL .= 'WHERE ' . join(' ', $whereStatements);
            }

            $imagesStatement = $this->pdo->prepare("
                SELECT
                    i.*
                FROM
                    image i
                " . $generatedSQL . "
                LIMIT
                    :start, :num
            ");

            // we have to bindValue each statement, as we can't provide PDO::PARAM_INT when using ->execute()
            $imagesStatement->bindValue(':start', $start, \PDO::PARAM_INT);
            $imagesStatement->bindValue(':num', (int) $query->num(), \PDO::PARAM_INT);

            foreach($whereValues as $placeholder => $value)
            {
                $imagesStatement->bindValue($placeholder, $value);
            }

            $imagesStatement->execute();

            $images = $imagesStatement->fetchAll(\PDO::FETCH_ASSOC);

            if ($query->returnMetadata()) {
                // build array with all images referenced by their imageidentifier
                $imagesByIdentifier = array();

                foreach($images as $image)
                {
                    $imagesByIdentifier[$image['imageIdentifier']] = $image;
                }

                // returns metadata by imageidentifier
                $metadataByIdentifier = $this->getMetadataForImages(array_keys($imagesByIdentifier));

                foreach($metadataByIdentifier as $identifier => $metadata)
                {
                    $imagesByIdentifier[$identifier]['metadata'] = $metadata;
                }

                // replace array with images array with added metadata
                $images = array_values($imagesByIdentifier);
            }
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to search for images: ' . $e->getMessage(), 500, $e);
        }

        return $images;
    }

    /**
     * @see PHPIMS\Database\DriverInterface::load()
     */
    public function load($publicKey, $imageIdentifier, Image $image) {
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

            $data = $loadImageStatement->fetch(\PDO::FETCH_ASSOC);
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

    /**
     * Get metadata for an array of image identifiers.
     *
     * @param array $imageIdentifiers A list of image identifiers to fetch data for.
     */
    protected function getMetadataForImages(array $imageIdentifiers)
    {
        $metadata = array();

        foreach($imageIdentifiers as $imageIdentifier)
        {
            $metadata[$imageIdentifier] = array();
        }

        try {
            $getMetadataStatement = $this->pdo->prepare("
                SELECT
                    m.imageIdentifier, m.field, m.value
                FROM
                    image_metadata m
                WHERE
                    m.imageIdentifier IN " . self::getPlaceholderExpression(count($imageIdentifiers)) . "
            ");

            $getMetadataStatement->execute($imageIdentifiers);

            $rows = $getMetadataStatement->fetchAll(\PDO::FETCH_ASSOC);

            foreach($rows as $row)
            {
                $metadata[$row['imageIdentifier']][$row['field']] = $row['value'];
            }

            $getMetadataStatement->closeCursor();
        } catch (\PDOException $e) {
            throw new DatabaseException('Unable to fetch image metadata: ' . $e->getMessage(), 500, $e);
        }

        return $metadata;
    }

    /**
     * Internal helper method to check if an image already exists in the database.
     *
     * @param string $imageIdentifier The identifier of the image we're checking if already exists
     */
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

    /**
     * Helper method to generate a placeholder string for multi-row inserts.
     *
     * @param int $columns Number of columns in each row
     * @param int $rows Number of rows
     */
    static protected function getInsertValuesString($columns, $rows)
    {
        $eachValueStr = self::getPlaceholderExpression($columns);

        // generate (?, ?), (?, ?), (?, ?)
        $completeInsertStr = substr(str_repeat($eachValueStr . ', ', $rows), 0, -2);

        return $completeInsertStr;
    }

    /**
     * Helper method to generate a placeholder string for a single column row.
     *
     * @param int $columns Number of columns to generate placeholder expression with
     */
    static protected function getPlaceholderExpression($columns)
    {
        // generate (?, ?, ?, ...., ?)
        $eachValueStr = '(' . substr(str_repeat('?, ', $columns), 0, -2) . ')';

        return $eachValueStr;
    }
}
