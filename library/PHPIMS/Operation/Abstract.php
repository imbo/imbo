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
 * @subpackage Operations
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * Abstract operation class
 *
 * @package PHPIMS
 * @subpackage Operations
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
abstract class PHPIMS_Operation_Abstract {
    /**
     * The current hash value (if present)
     *
     * @param string
     */
    protected $hash = null;

    /**
     * The database driver
     *
     * @var PHPIMS_Database_Driver_Interface
     */
    protected $database = null;

    /**
     * The storage driver
     *
     * @var PHPIMS_Storage_Driver_Interface
     */
    protected $storage = null;

    /**
     * Class constructor
     *
     * @param string $hash An optional hash for the operation to work with
     */
    public function __construct($hash = null) {
        if ($hash !== null) {
            $this->setHash($hash);
        }
    }

    /**
     * Init method
     *
     * @param array $config Configuration passed on from the front controller
     */
    public function init(array $config) {
        if (!empty($config['database']['driver'])) {
            $params = array();

            if (isset($config['database']['params'])) {
                $params = $config['database']['params'];
            }

            $this->setDatabase(new $config['database']['driver']($params));
        }

        if (!empty($config['storage']['driver'])) {
            $params = array();

            if (isset($config['storage']['params'])) {
                $params = $config['storage']['params'];
            }

            $this->setStorage(new $config['storage']['driver']($params));
        }

        return $this;
    }

    /**
     * Get the current hash
     *
     * @return string
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * Set the hash property
     *
     * @param string $hash The hash to set
     * @return PHPIMS_Operation_Abstract
     */
    public function setHash($hash) {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get the database driver
     *
     * @return PHPIMS_Database_Driver_Interface
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * Set the database driver
     *
     * @param PHPIMS_Database_Driver_Interface $driver The driver instance
     * @return PHPIMS_FrontController
     */
    public function setDatabase(PHPIMS_Database_Driver_Interface $driver) {
        $this->database = $driver;

        return $this;
    }

    /**
     * Get the storage driver
     *
     * @return PHPIMS_Storage_Driver_Interface
     */
    public function getStorage() {
        return $this->storage;
    }

    /**
     * Set the storage driver
     *
     * @param PHPIMS_Storage_Driver_Interface $driver The driver instance
     * @return PHPIMS_FrontController
     */
    public function setStorage(PHPIMS_Storage_Driver_Interface $driver) {
        $this->storage = $driver;

        return $this;
    }

    /**
     * Execute the operation
     *
     * @throws PHPIMS_Operation_Exception
     */
    abstract public function exec();
}