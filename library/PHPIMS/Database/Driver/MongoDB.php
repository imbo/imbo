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
     * Method to check if an image hash is valid for this driver
     *
     * @param string $hash The hash to check
     * @return boolean Returns true if valid, false otherwise
     */
    static public function isValidHash($hash) {
        return (bool) preg_match('/^[a-zA-Z0-9]{24}$/', $hash);
    }

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
    public function insertNewImage(PHPIMS_Image $image) {
        $data = $image->getMetadata();

        $data['_name']  = $image->getFilename();
        $data['_size']  = $image->getFilesize();
        $data['_added'] = time();
        $data['_md5']   = md5_file($image->getPath());

        // Add some special data about the image
        $fp = finfo_open(FILEINFO_MIME_TYPE);
        $data['_mime'] = finfo_file($fp, $image->getPath());
        finfo_close($fp);

        try {
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
            $this->getCollection()->remove(array('_id' => new MongoId($hash)), array('justOne' => true, 'safe' => true));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to delete image data', 500, $e);
        }

        return true;
    }

    /**
     * Edit an image
     *
     * @param string $hash The unique ID of the image to edit
     * @param array $metadata An array with metadata
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Database_Exception
     */
    public function editImage($hash, array $metadata) {
        try {
            $this->getCollection()->update(
                array('_id' => new MongoID($hash)),
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
    public function getImageMetadata($hash) {
        try {
            $data = $this->getCollection()->findOne(array('_id' => new MongoID($hash)));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to fetch image metadata', 500, $e);
        }

        return $data;
    }

    /**
     * Get the mime-type of an image
     *
     * @param string $hash The unique ID of the image to get the mime-type of
     * @return string The mime type that can be placed in a Content-Type header
     * @throws PHPIMS_Database_Exception
     */
    public function getImageMimetype($hash) {
        try {
            $data = $this->getCollection()->findOne(array('_id' => new MongoID($hash)), array('_mime'));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to fetch image metadata', 500, $e);
        }

        return $data['_mime'];
    }

    /**
     * Get the file size of an image
     *
     * @param string $hash The unique ID of the image to get the size of
     * @return int The size of the file in bytes
     * @throws PHPIMS_Database_Exception
     */
    public function getImageSize($hash) {
        try {
            $data = $this->getCollection()->findOne(array('_id' => new MongoID($hash)), array('_size'));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to fetch image metadata', 500, $e);
        }

        return (int) $data['_size'];
    }
}