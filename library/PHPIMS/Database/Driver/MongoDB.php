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
class PHPIMS_Database_Driver_MongoDB implements PHPIMS_Database_Driver_Interface {
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
    public function isValidHash($hash) {
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
     * extends PHPIMS_Database_Driver_Exception.
     *
     * @param PHPIMS_Image $image The image object to insert
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Database_Driver_Exception
     */
    public function insertNewImage(PHPIMS_Image $image) {
        $collection = $this->getDatabase()->images;

        $data = array(
            'name' => $image->getFilename(),
            'size' => $image->getFilesize(),
        );

        try {
            $collection->insert($data, array('safe' => true));
        } catch (MongoCursorException $e) {
            throw new PHPIMS_Database_Exception('Could not insert image', 0, $e);
        }

        $image->setId((string) $data['_id']);

        return true;
    }
}