<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * MongoDB database driver
 *
 * A MongoDB database driver for PHPIMS
 *
 * Valid parameters for this driver:
 *
 * Required:
 * - <none>
 *
 * Optional:
 * - (string) database => Name of the database. Defaults to 'phpims'
 * - (string) collection => Name of the collection to store data in. Defaults to 'images'
 *
 * @package PHPIMS
 * @subpackage DatabaseDriver
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Database_Driver_MongoDB extends PHPIMS_Database_Driver_Abstract {
    /**
     * A MongoDB connection
     *
     * @var MongoDB
     */
    protected $mongo = null;

    /**
     * The name of the database we want to use
     *
     * @var string
     */
    protected $databaseName = 'phpims';

    /**
     * Name of the collection to store the images in
     *
     * @var string
     */
    protected $collectionName = 'images';

    /**
     * The collection used by the driver
     *
     * @var MongoCollection
     */
    protected $collection = null;

    /**
     * Get the database name
     *
     * @return string
     */
    public function getDatabaseName() {
        return $this->databaseName;
    }

    /**
     * Set the database name
     *
     * @param string $name The name to set
     * @return PHPIMS_Database_Driver_MongoDB
     */
    public function setDatabaseName($name) {
        $this->databaseName = $name;

        return $this;
    }

    /**
     * Get the collection name
     *
     * @return string
     */
    public function getCollectionName() {
        return $this->collectionName;
    }

    /**
     * Set the collection name
     *
     * @param string $name The name to set
     * @return PHPIMS_Database_Driver_MongoDB
     */
    public function setCollectionName($name) {
        $this->collectionName = $name;

        return $this;
    }

    /**
     * Get the database
     *
     * @return MongoDB
     */
    public function getDatabase() {
        if ($this->mongo === null) {
            // @codeCoverageIgnoreStart
            $mongo = new Mongo();
            $this->mongo = $mongo->{$this->databaseName};
        }
        // @codeCoverageIgnoreEnd

        return $this->mongo;
    }

    /**
     * Get the mongo collection
     *
     * @return MongoCollection
     */
    public function getCollection() {
        if ($this->collection === null) {
            // @codeCoverageIgnoreStart
            $this->setCollection($this->getDatabase()->{$this->collectionName});
        }
        // @codeCoverageIgnoreEnd

        return $this->collection;
    }

    /**
     * Set the collection
     *
     * @param MongoCollection $collection The collection to set
     * @return PHPIMS_Database_Driver_MongoDB
     */
    public function setCollection(MongoCollection $collection) {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Set the database instance
     *
     * @param MongoDB $database The MongoDB database to set
     * @return PHPIMS_Database_Driver_MongoDB
     */
    public function setDatabase(MongoDB $database) {
        $this->mongo = $database;

        return $this;
    }

    /**
     * Insert a new image
     *
     * This method will insert a new image into the database. The method should update the $image
     * object if successfull by setting the newly created ID. On errors throw exceptions that
     * extends PHPIMS_Database_Exception.
     *
     * @param PHPIMS_Image $image The image object to insert
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Database_Exception
     */
    public function insertImage(PHPIMS_Image $image) {
        $data = $image->getMetadata();

        $data['name']  = $image->getFilename();
        $data['size']  = $image->getFilesize();
        $data['added'] = time();
        $data['hash']  = $image->getHash();
        $data['mime']  = $image->getMimeType();

        try {
            // See if the image already exists
            $row = $this->getCollection()->findOne(array('hash' => $data['hash']));

            if ($row) {
                throw new PHPIMS_Database_Exception('Image already exists', 400);
            }

            $this->getCollection()->insert($data, array('safe' => true));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to save image data', 500, $e);
        }

        $image->setId((string) $data['_id']);

        return true;
    }

    /**
     * Delete an image from the database
     *
     * @param string $hash The unique ID of the image to delete
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Database_Exception
     */
    public function deleteImage($hash) {
        try {
            $this->getCollection()->remove(array('hash' => $hash), array('justOne' => true, 'safe' => true));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to delete image data', 500, $e);
        }

        return true;
    }

    /**
     * Edit metadata
     *
     * @param string $hash The unique ID of the image to edit
     * @param array $metadata An array with metadata
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Database_Exception
     */
    public function editMetadata($hash, array $metadata) {
        try {
            $this->getCollection()->update(
                array('hash' => $hash),
                array('$set' => $metadata),
                array(
                    'safe' => true,
                    'multiple' => false
                )
            );
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to edit image data', 500, $e);
        }

        return true;
    }

    /**
     * Get all metadata associated with an image
     *
     * @param string $hash The unique ID of the image to get metadata from
     * @return array Returns the metadata as an array
     * @throws PHPIMS_Database_Exception
     */
    public function getMetadata($hash) {
        try {
            $data = $this->getCollection()->findOne(array('hash' => $hash));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to fetch image metadata', 500, $e);
        }

        return $data;
    }

    /**
     * Delete all metadata associated with an image (with the exception of the MongoID and the hash
     * itself)
     *
     * @param string $hash The unique ID of the image to delete metadata from
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Database_Exception
     */
    public function deleteMetadata($hash) {

    }
}