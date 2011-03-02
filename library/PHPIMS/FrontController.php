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
     * Name of the operation factory class
     *
     * @var string
     */
    protected $operationFactoryClass = 'PHPIMS_Operation';

    /**
     * Class constructor
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config = null) {
        if ($config !== null) {
            $this->setConfig($config);
        }
    }

    /**
     * Wether or not the method is valid
     *
     * @param string $method The current method
     * @return boolean True if $method is valid, false otherwise
     */
    static public function isValidMethod($method) {
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
     * Get the operationFactoryClass property
     *
     * @return string
     */
    public function getOperationFactoryClass() {
        return $this->operationFactoryClass;
    }

    /**
     * Set the operationFactoryClass property
     *
     * @param string $className The name of the class
     * @return PHPIMS_FrontController
     */
    public function setOperationFactoryClass($className) {
        $this->operationFactoryClass = $className;

        return $this;
    }

    /**
     * Generate an operation object based on some parameters
     *
     * @param string $method The HTTP method
     * @param string $hash   Optional hash
     * @param string $extra  Optional extra argument
     * @throws PHPIMS_Exception
     * @return PHPIMS_Operation_Abstract
     */
    protected function resolveOperation($method, $hash = null, $extra = null) {
        if ($method === self::GET && !empty($hash)) {
            if ($extra === 'meta') {
                $operation = 'PHPIMS_Operation_GetMetadata';
            } else {
                $operation = 'PHPIMS_Operation_GetImage';
            }
        } else if ($method === self::POST) {
            if (empty($hash)) {
                $operation = 'PHPIMS_Operation_AddImage';
            } else {
                $operation = 'PHPIMS_Operation_EditImage';
            }
        } else if ($method === self::DELETE && !empty($hash)) {
            $operation = 'PHPIMS_Operation_DeleteImage';
        } else {
            throw new PHPIMS_Exception('Unsupported operation');
        }

        $factoryClass = $this->getOperationFactoryClass();

        return $factoryClass::factory($operation, $hash);;
    }

    /**
     * Handle a request
     *
     * @param string $method The HTTP method (one of the defined constants)
     * @param string $url    The url accessed
     * @throws PHPIMS_Exception
     */
    public function handle($method, $url) {
        if (!self::isValidMethod($method)) {
            throw new PHPIMS_Exception('Invalid HTTP method: ' . $method);
        }

        // Remove starting and trailing slashes
        $url = trim($url, '/');
        $parts = explode('/', $url);
        $hash = $parts[0];
        $extra = null;

        if (isset($parts[1])) {
            $extra = $parts[1];
        }

        $databaseDriver = $this->config['database']['driver'];

        if (!empty($hash) && !$databaseDriver::isValidHash($hash)) {
            throw new PHPIMS_Exception('Invalid hash: ' . $hash);
        }

        $operation = $this->resolveOperation($method, $hash, $extra);

        try {
            $response = $operation->init($this->config)->exec();
        } catch (PHPIMS_Operation_Exception $e) {
            print($e->getMessage());
            exit;
        }

        $code = $response->getCode();
        $header = sprintf("HTTP/1.0 %d %s", $code, PHPIMS_Server_Response::$codes[$code]);
        header($header);

        foreach ($response->getHeaders() as $header) {
            header($header);
        }

        print($response);
    }
}