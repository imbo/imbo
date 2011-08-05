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

use PHPIMs\Image\ImageInterface;
use PHPIMS\Database\DriverInterface as Database;
use PHPIMS\Storage\DriverInterface as Storage;
use PHPIMS\Response\ResponseInterface;
use PHPIMS\Operation\Exception as OperationException;
use PHPIMS\Operation\PluginInterface as Plugin;
use PHPIMS\Operation\Plugin\Auth;
use PHPIMS\Operation\Plugin\IdentifyImage;
use PHPIMS\Operation\Plugin\ManipulateImage;
use PHPIMS\Operation\Plugin\PrepareImage;

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
    private $resource;

    /**
     * The current image identifier
     *
     * @param string
     */
    private $imageIdentifier;

    /**
     * HTTP method
     *
     * @var string
     */
    private $method;

    /**
     * The database driver
     *
     * @var PHPIMS\Database\DriverInterface
     */
    private $database;

    /**
     * The storage driver
     *
     * @var PHPIMS\Storage\DriverInterface
     */
    private $storage;

    /**
     * Image instance
     *
     * The image object is populated with en empty instance of PHPIMS\Image\ImageInterface when the
     * operation initializes.
     *
     * @var PHPIMS\Image\ImageInterface
     */
    private $image;

    /**
     * Response instance
     *
     * The response object is populated with en empty instance of PHPIMS\Response\ResponseInterface
     * when the operation initializes.
     *
     * @var PHPIMS\Response\ResponseInterface
     */
    private $response;

    /**
     * Array of plugins
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
     * Current public key if it exists
     *
     * @var string
     */
    private $publicKey;

    /**
     * Current private key if it exists
     *
     * @var string
     */
    private $privateKey;

    /**
     * Class constructor
     *
     * @param PHPIMS\Database\DriverInterface $database Database driver
     * @param PHPIMS\Storage\DriverInterface $storage Storage driver
     */
    public function __construct(Database $database = null, Storage $storage = null) {
        if ($database !== null) {
            $this->setDatabase($database);
        }

        if ($storage !== null) {
            $this->setStorage($storage);
        }

        // Register internal plugins
        $this->registerPlugin(new Auth())
             ->registerPlugin(new IdentifyImage())
             ->registerPlugin(new ManipulateImage())
             ->registerPlugin(new PrepareImage());
    }

    /**
     * Register a plugin
     *
     * @param PHPIMS\Operation\PluginInterface $plugin Plugin instance
     * @return PHPIMS\Operation
     */
    public function registerPlugin(Plugin $plugin) {
        $this->plugins[] = $plugin;

        return $this;
    }

    /**
     * Get the current operation name
     *
     * @return string
     */
    public function getOperationName() {
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
     * @param PHPIMS\Database\DriverInterface $database The driver instance
     * @return PHPIMS\Operation
     */
    public function setDatabase(Database $database) {
        $this->database = $database;

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
     * @param PHPIMS\Storage\DriverInterface $storage The driver instance
     * @return PHPIMS\Operation
     */
    public function setStorage(Storage $storage) {
        $this->storage = $storage;

        return $this;
    }

    /**
     * Get the current image
     *
     * @return PHPIMS\Image\ImageInterface
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set the image
     *
     * @param PHPIMS\Image\ImageInterface $image The image object to set
     * @return PHPIMS\Operation
     */
    public function setImage(ImageInterface $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * Get the response object
     *
     * @return PHPIMS\Response\ResponseInterface
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Set the response instance
     *
     * @param PHPIMS\Response\ResponseInterface $response A response object
     * @return PHPIMS\Operation
     */
    public function setResponse(ResponseInterface $response) {
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
     * Get the public key
     *
     * @return string
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * Set the public key
     *
     * @param string $key
     * @return PHPIMS\Operation
     */
    public function setPublicKey($key) {
        $this->publicKey = $key;

        return $this;
    }

    /**
     * Get the private key
     *
     * @return string
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * Set the private key
     *
     * @param string $key
     * @return PHPIMS\Operation
     */
    public function setPrivateKey($key) {
        $this->privateKey = $key;

        return $this;
    }

    /**
     * Run the operation
     *
     * This method will trigger registered plugins along with the main operation.
     */
    public function run() {
        $operationName = $this->getOperationName();
        $preExecKey = $operationName . 'PreExec';
        $postExecKey = $operationName . 'PostExec';

        $plugins = array(
            'preExec'  => array(),
            'postExec' => array(),
        );

        foreach ($this->plugins as $plugin) {
            if (isset($plugin::$events[$preExecKey])) {
                $plugins['preExec'][$plugin::$events[$preExecKey]] = $plugin;
            }

            if (isset($plugin::$events[$postExecKey])) {
                $plugins['postExec'][$plugin::$events[$postExecKey]] = $plugin;
            }
        }

        // Sort by keys to execute plugins in correct order
        ksort($plugins['preExec']);
        ksort($plugins['postExec']);

        // Trigger plugins who want to run before the operation
        foreach ($plugins['preExec'] as $plugin) {
            $plugin->exec($this);
        }

        // Run the operation
        $this->exec();

        // Trigger plugins who want to run after the operation
        foreach ($plugins['postExec'] as $plugin) {
            $plugin->exec($this);
        }
    }
}
