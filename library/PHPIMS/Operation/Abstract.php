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
     * @var PHPIMS_Database_Driver_Abstract
     */
    protected $database = null;

    /**
     * The storage driver
     *
     * @var PHPIMS_Storage_Driver_Abstract
     */
    protected $storage = null;

    /**
     * Plugins for the current operation
     *
     * @var array An array of objects extending the PHPIMS_Operation_Plugin_Abstract class
     */
    protected $plugins = array();

    /**
     * Image instance
     *
     * The image object is populated with en empty instance of PHPIMS_Image when the operation
     * initializes.
     *
     * @var PHPIMS_Image
     */
    protected $image = null;

    /**
     * Response instance
     *
     * The response object is populated with en empty instance of PHPIMS_Server_Response when the
     * operation initializes.
     *
     * @var PHPIMS_Image
     */
    protected $response = null;

    /**
     * Class constructor
     *
     * @param string $hash An optional hash for the operation to work with
     * @param PHPIMS_Image $image Optional image object
     * @param PHPIMS_Server_Response $response Optional response object
     */
    public function __construct($hash = null, PHPIMS_Image $image = null, PHPIMS_Server_Response $response = null) {
        if ($hash !== null) {
            $this->setHash($hash);
        }

        if ($image === null) {
            $image = new PHPIMS_Image();
        }

        if ($response === null) {
            $response = new PHPIMS_Server_Response();
        }

        $this->setImage($image);
        $this->setResponse($response);
    }

    /**
     * Init method
     *
     * @param array $config Configuration passed on from the front controller
     * @return PHPIMS_Operation_Abstract
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

        if (!empty($config['plugins'][__CLASS__])) {
            foreach ($config['plugins'][__CLASS__] as $pluginName => $pluginParams) {
                $plugin = new $pluginName($pluginParams, $this);
                $this->addPlugin($plugin);
            }
        }

        return $this;
    }

    /**
     * Pre exec method
     *
     * This method will trigger the preExec method on all registered plugins
     *
     * @return PHPIMS_Operation_Abstract
     */
    public function preExec() {
        array_map(function ($plugin) {
            try {
                $plugin->preExec();
            } catch (PHPIMS_Operation_Plugin_Exception $e) {
                trigger_error(sprintf('Plugin "%s" failed: %s', get_class($plugin), $e->getMessage()), E_USER_WARNING);
            }
        }, $this->getPlugins());

        return $this;
    }

    /**
     * Post exec method
     *
     * This method will trigger the postExec method on all registered plugins
     *
     * @return PHPIMS_Operation_Abstract
     */
    public function postExec() {
        array_map(function ($plugin) {
            try {
                $plugin->postExec();
            } catch (PHPIMS_Operation_Plugin_Exception $e) {
                trigger_error(sprintf('Plugin "%s" failed: %s', get_class($plugin), $e->getMessage()), E_USER_WARNING);
            }
        }, $this->getPlugins());

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
     * @return PHPIMS_Database_Driver_Abstract
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * Set the database driver
     *
     * @param PHPIMS_Database_Driver_Abstract $driver The driver instance
     * @return PHPIMS_Operation_Abstract
     */
    public function setDatabase(PHPIMS_Database_Driver_Abstract $driver) {
        $this->database = $driver;

        return $this;
    }

    /**
     * Get the storage driver
     *
     * @return PHPIMS_Storage_Driver_Abstract
     */
    public function getStorage() {
        return $this->storage;
    }

    /**
     * Set the storage driver
     *
     * @param PHPIMS_Storage_Driver_Abstract $driver The driver instance
     * @return PHPIMS_Operation_Abstract
     */
    public function setStorage(PHPIMS_Storage_Driver_Abstract $driver) {
        $this->storage = $driver;

        return $this;
    }

    /**
     * Return the plugins
     *
     * @return array
     */
    public function getPlugins() {
        return $this->plugins;
    }

    /**
     * Set all plugins (will remove ones already registered)
     *
     * @param array $plugins An array of objects extending PHPIMS_Operation_Plugin_Abstract
     * @return PHPIMS_Operation_Abstract
     */
    public function setPlugins(array $plugins) {
        $this->plugins = $plugins;

        return $this;
    }

    /**
     * Add a single plugin
     *
     * @param PHPIMS_Operation_Plugin_Abstract $plugin The plugin to append to the array of plugins
     * @return PHPIMS_Operation_Abstract
     */
    public function addPlugin(PHPIMS_Operation_Plugin_Abstract $plugin) {
        $this->plugins[] = $plugin;

        return $this;
    }

    /**
     * Get the current image
     *
     * @return PHPIMS_Image
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set the image
     *
     * @param PHPIMS_Image $image The image object to set
     * @return PHPIMS_Operation_Abstract
     */
    public function setImage(PHPIMS_Image $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * Get the response object
     *
     * @return PHPIMS_Server_Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Set the response instance
     *
     * @param PHPIMS_Server_Response $response A response object
     * @return PHPIMS_Operation_Abstract
     */
    public function setResponse(PHPIMS_Server_Response $response) {
        $this->response = $response;

        return $this;
    }

    /**
     * Execute the operation
     *
     * Operations must implement this method and return a PHPIMS_Server_Response object to return
     * to the client.
     *
     * @return PHPIMS_Operation_Abstract
     * @throws PHPIMS_Operation_Exception
     */
    abstract public function exec();
}