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

namespace PHPIMS;

use PHPIMS\Database\DriverInterface as DatabaseDriver;
use PHPIMS\Storage\DriverInterface as StorageDriver;
use PHPIMS\Server\Response;
use PHPIMS\Operation\Exception as OperationException;

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
abstract class Operation {
    /**
     * The accessed resource
     *
     * @var string
     */
    private $resource = null;

    /**
     * The current image identifier
     *
     * @param string
     */
    private $imageIdentifier = null;

    /**
     * HTTP method
     *
     * @var string
     */
    private $method = null;

    /**
     * The database driver
     *
     * @var PHPIMS\Database\DriverInterface
     */
    private $database = null;

    /**
     * The storage driver
     *
     * @var PHPIMS\Storage\DriverInterface
     */
    private $storage = null;

    /**
     * Image instance
     *
     * The image object is populated with en empty instance of PHPIMS\Image when the operation
     * initializes.
     *
     * @var PHPIMS\Image
     */
    private $image = null;

    /**
     * Response instance
     *
     * The response object is populated with en empty instance of PHPIMS\Server\Response when the
     * operation initializes.
     *
     * @var PHPIMS\Image
     */
    private $response = null;

    /**
     * Array of class names to plugins to execute. The array has two elements, 'preExec' and
     * 'postExec' which both are numerically indexed.
     *
     * @var array
     */
    private $plugins = array();

    /**
     * Configuration passed from the front controller
     *
     * @var array
     */
    private $config = array();

    /**
     * Class constructor
     *
     * @param PHPIMS\Database\DriverInterface $database Database driver
     * @param PHPIMS\Storage\DriverInterface $storage Storage driver
     */
    public function __construct(DatabaseDriver $database, StorageDriver $storage) {
        $this->database = $database;
        $this->storage  = $storage;
    }

    /**
     * Initialize plugins for this operation
     *
     * @param array $config Part of the confguration array passed from the front controller
     */
    protected function initPlugins(array $config) {
        // Operation name
        $operationName = $this->getOperationName();

        // Loop through the plugin paths, and see if there are any plugins that wants to execute
        // before and/or after this operation. Also sort them based in the priorities set in each
        // plugin class.
        $pluginPaths = array(
            dirname(__DIR__) => 'PHPIMS\\Operation\\Plugin\\',
        );

        // Append plugin paths from configuration
        foreach ($config as $spec) {
            $pluginPaths[$spec['path']] = isset($spec['prefix']) ? $spec['prefix'] : '';
        }

        // Initialize array for the plugins that will be executed
        $plugins = array(
            'preExec'  => array(),
            'postExec' => array()
        );

        foreach ($pluginPaths as $path => $prefix) {
            $path = rtrim($path, '/̈́') . '/';

            if (!empty($prefix)) {
                $path .= str_replace('\\', '/', $prefix);
            }

            if (empty($path) || !is_dir($path)) {
                continue;
            }

            $iterator = new \GlobIterator($path . '*Plugin.php');

            foreach ($iterator as $file) {
                $className = $prefix . $file->getBasename('.php');

                if (is_subclass_of($className, 'PHPIMS\\Operation\\Plugin')) {
                    $events = $className::$events;

                    $key = $operationName . 'PreExec';

                    if (isset($events[$key])) {
                        $priority = (int) $events[$key];
                        $plugin = new $className();
                        $plugins['preExec'][$priority] = $plugin;
                    }

                    $key = $operationName . 'PostExec';

                    if (isset($events[$key])) {
                        $priority = (int) $events[$key];
                        $plugin = new $className();
                        $plugins['postExec'][$priority] = $plugin;
                    }
                }
            }
        }

        // Sort to get the correct order
        ksort($plugins['preExec']);
        ksort($plugins['postExec']);

        $this->setPlugins($plugins);
    }

    /**
     * Get plugins array
     *
     * @return array
     */
    protected function getPlugins() {
        return $this->plugins;
    }

    /**
     * Set plugins array
     *
     * @param array $plugins Associative array with two keys: 'preExec' and 'postExec' which both
     *                       should be sorted numerically indexed arrays.
     */
    protected function setPlugins(array $plugins) {
        $this->plugins = $plugins;
    }

    /**
     * Init method
     *
     * @param array $config Configuration passed on from the front controller
     * @return PHPIMS\Operation
     * @codeCoverageIgnore
     */
    public function init(array $config) {
        $this->setConfig($config);

        $this->initPlugins($config['plugins']);

        return $this;
    }

    /**
     * Get the current operation name
     *
     * @return string
     */
    protected function getOperationName() {
        $className = get_class($this);

        $operationName = substr($className, strrpos($className, '\\') + 1);
        $operationName = lcfirst($operationName);

        return $operationName;
    }

    /**
     * Get the accessed resource
     *
     * @return string
     */
    public function getResource() {
        return $this->resource;
    }

    /**
     * Set the accessed resource
     *
     * @param string $resource
     * @return PHPIMS\Operation
     */
    public function setResource($resource) {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get the current image identifier
     *
     * @return string
     */
    public function getImageIdentifier() {
        return $this->imageIdentifier;
    }

    /**
     * Set the image identifier property
     *
     * @param string $imageIdentifier The identifier to set
     * @return PHPIMS\Operation
     */
    public function setImageIdentifier($imageIdentifier) {
        $this->imageIdentifier = $imageIdentifier;

        return $this;
    }

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Set the method
     *
     * @param string $method The method to set
     * @return PHPIMS\Operation
     */
    public function setMethod($method) {
        $this->method = $method;

        return $this;
    }

    /**
     * Get the database driver
     *
     * @return PHPIMS\Database\DriverInterface
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * Set the database driver
     *
     * @param PHPIMS\Database\DriverInterface $driver The driver instance
     * @return PHPIMS\Operation
     */
    public function setDatabase(DatabaseDriver $driver) {
        $this->database = $driver;

        return $this;
    }

    /**
     * Get the storage driver
     *
     * @return PHPIMS\Storage\DriverInterface
     */
    public function getStorage() {
        return $this->storage;
    }

    /**
     * Set the storage driver
     *
     * @param PHPIMS\Storage\DriverInterface $driver The driver instance
     * @return PHPIMS\Operation
     */
    public function setStorage(StorageDriver $driver) {
        $this->storage = $driver;

        return $this;
    }

    /**
     * Get the current image
     *
     * @return PHPIMS\Image
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set the image
     *
     * @param PHPIMS\Image $image The image object to set
     * @return PHPIMS\Operation
     */
    public function setImage(Image $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * Get the response object
     *
     * @return PHPIMS\Server\Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Set the response instance
     *
     * @param PHPIMS\Server\Response $response A response object
     * @return PHPIMS\Operation
     */
    public function setResponse(Response $response) {
        $this->response = $response;

        return $this;
    }

    /**
     * Get the configuration array
     *
     * @param string $key Optional key. If not specified the whole array will be returned
     * @return array
     */
    public function getConfig($key = null) {
        if ($key !== null) {
            return $this->config[$key];
        }

        return $this->config;
    }

    /**
     * Set the configuration array
     *
     * @param array $config Configuration array
     * @return PHPIMS\Operation
     */
    public function setConfig(array $config) {
        $this->config = $config;

        return $this;
    }

    /**
     * Trigger for registered "preExec" plugins
     *
     * @return PHPIMS\Operation
     * @throws PHPIMS\Operation\Plugin\Exception
     */
    public function preExec() {
        foreach ($this->plugins['preExec'] as $plugin) {
            $plugin->exec($this);
        }

        return $this;
    }

    /**
     * Trigger for registered "postExec" plugins
     *
     * @return PHPIMS\Operation
     * @throws PHPIMS\Operation\Plugin\Exception
     */
    public function postExec() {
        foreach ($this->plugins['postExec'] as $plugin) {
            $plugin->exec($this);
        }

        return $this;
    }

    /**
     * Factory method
     *
     * @param string $className The name of the operation class to instantiate
     * @param PHPIMS\Database\DriverInterface $database Database driver
     * @param PHPIMS\Storage\DriverInterface $storage Storage driver
     * @param string $resource The accessed resource
     * @param string $method The HTTP method used
     * @param string $imageIdentifier Optional Image identifier
     * @return PHPIMS\OperationInterface
     * @throws PHPIMS\Operation\Exception
     */
    static public function factory($className, DatabaseDriver $database, StorageDriver $storage, $resource, $method, $imageIdentifier = null) {
        switch ($className) {
            case 'PHPIMS\\Operation\\AddImage':
            case 'PHPIMS\\Operation\\DeleteImage':
            case 'PHPIMS\\Operation\\DeleteImageMetadata':
            case 'PHPIMS\\Operation\\EditImageMetadata':
            case 'PHPIMS\\Operation\\GetImage':
            case 'PHPIMS\\Operation\\GetImages':
            case 'PHPIMS\\Operation\\GetImageMetadata':
                $operation = new $className($database, $storage);
                $operation->setResource($resource)
                          ->setImageIdentifier($imageIdentifier)
                          ->setMethod($method)
                          ->setImage(new Image())
                          ->setResponse(new Response());

                return $operation;
            default:
                throw new OperationException('Invalid operation', 500);
        }
    }
}