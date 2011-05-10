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

use PHPIMS\Database\DriverInterface as Database;
use PHPIMS\Storage\DriverInterface as Storage;
use PHPIMS\Server\Response;
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
     * Class constructor
     *
     * @param PHPIMS\Database\DriverInterface $database Database driver
     * @param PHPIMS\Storage\DriverInterface $storage Storage driver
     */
    public function __construct(Database $database, Storage $storage) {
        $this->database = $database;
        $this->storage  = $storage;

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
    static public function factory($className, Database $database, Storage $storage, $resource, $method, $imageIdentifier = null) {
        switch ($className) {
            case 'PHPIMS\\Operation\\AddImage':
            case 'PHPIMS\\Operation\\DeleteImage':
            case 'PHPIMS\\Operation\\DeleteImageMetadata':
            case 'PHPIMS\\Operation\\EditImageMetadata':
            case 'PHPIMS\\Operation\\GetImage':
            case 'PHPIMS\\Operation\\GetImages':
            case 'PHPIMS\\Operation\\GetImageMetadata':
            case 'PHPIMS\\Operation\\HeadImage':
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
