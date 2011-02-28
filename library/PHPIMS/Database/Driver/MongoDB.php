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
    static protected $database = null;

    /**
     * Method to check if an image hash is valid for this driver
     *
     * @param string $hash The hash to check
     * @return boolean Returns true if valid, false otherwise
     */
    static public function isValidHash($hash) {
        return preg_match('/^[a-zA-Z0-9]{24}$/', $hash);
    }

    /**
     * Get the database
     *
     * @return MongoDB
     */
    public function getDatabase() {
        if (self::$database === null) {
            $mongo = new Mongo();
            self::$database = $mongo->phpims;
        }

        return self::$database;
    }

    /**
     * Set the database instance
     *
     * @param MongoDB $database The MongoDB database to set
     * @return PHPIMS_Database_Driver_MongoDB
     */
    public function setDatabase(MongoDB $database) {
        self::$database = $database;

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
        $data['name'] = $image->getFilename();
        $data['size'] = $image->getFilesize();

        try {
            $collection = $this->getDatabase()->images;
            $collection->insert($data, array('safe' => true));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Could not save image data to database', 0, $e);
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
            $mongoCollection = $this->getDatabase()->images;
            $mongoCollection->remove(array('_id' => new MongoId($hash)), array('justOne' => true, 'safe' => true));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Could not delete image', 0, $e);
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
            $mongoCollection = $this->getDatabase()->images;
            $mongoCollection->update(
                array('_id' => new MongoID($hash)),
                array('$set' => $metadata),
                array(
                    'safe' => true,
                    'multiple' => false
                )
            );
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to edit image data', 0, $e);
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
            $mongoCollection = $this->getDatabase()->images;
            $data = $mongoCollection->findOne(array('_id' => new MongoID($hash)));
        } catch (MongoException $e) {
            throw new PHPIMS_Database_Exception('Unable to get image metadata', 0, $e);
        }

        return $data;
    }
}