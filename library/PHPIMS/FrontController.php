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
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * Client that interacts with the server part of PHPIMS
 *
 * This client includes methods that can be used to easily interact with a PHPIMS server
 *
 * @package PHPIMS
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_FrontController {
    /**#@+
     * Supported HTTP methods
     *
     * @var string
     */
    const GET    = 'GET';
    const POST   = 'POST';
    const HEAD   = 'HEAD';
    const DELETE = 'DELETE';
    /**#@-*/

    /**
     * Configuration
     *
     * @var array
     */
    protected $config = null;

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
     * @param array $config Configuration array
     */
    public function __construct(array $config = null) {
        if ($config !== null) {
            $this->setConfig($config);

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
        }
    }

    /**
     * Wether or not the method is valid
     *
     * @param string $method The current method
     * @return boolean True if $method is valid, false otherwise
     */
    public function isValidMethod($method) {
        switch ($method) {
            case self::GET:
            case self::POST:
            case self::HEAD:
            case self::DELETE:
                return true;
            default:
                return false;
        }
    }

    /**
     * Get the config array
     *
     * @return array
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Set the config array
     *
     * @param array $config The configuration array to set
     * @return PHPIMS_FrontController
     */
    public function setConfig(array $config) {
        $this->config = $config;

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
     * Handle a request
     *
     * @param string $method The HTTP method (one of the defined constants)
     * @param string $path   The path accessed
     * @throws PHPIMS_Exception
     */
    public function handle($method, $path) {
        if (!$this->isValidMethod($method)) {
            throw new PHPIMS_Exception('Invalid HTTP method: ' . $method);
        }

        // Remove starting and trailing slashes
        $hash = trim($path, '/');

        if (!empty($hash) && !$this->getDatabase()->isValidHash($hash)) {
            throw new PHPIMS_Exception('Invalid hash: ' . $hash);
        }

        if ($method === self::GET && !empty($hash)) {
            $operation = new PHPIMS_Operation_GetImage();
        } else if ($method === self::POST) {
            if (empty($hash)) {
                $operation = new PHPIMS_Operation_AddImage();
            } else {
                $operation = new PHPIMS_Operation_EditImage();
            }
        } else if ($method === self::DELETE && !empty($hash)) {
            $operation = new PHPIMS_Operation_DeleteImage();
        } else {
            throw new PHPIMS_Exception('Unsupported operation');
        }

        if (!empty($hash)) {
            $operation->setHash($hash);
        }

        try {
            $operation->setFrontController($this)->exec();
        } catch (PHPIMS_Operation_Exception $e) {

        }
    }
}