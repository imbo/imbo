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

namespace PHPIMS;

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
class FrontController {
    /**#@+
     * Supported HTTP methods
     *
     * @var string
     */
    const GET    = 'GET';
    const POST   = 'POST';
    const HEAD   = 'HEAD';
    const DELETE = 'DELETE';
    const BREW   = 'BREW';
    /**#@-*/

    /**
     * Configuration
     *
     * @var array
     */
    private $config = null;

    /**
     * Class constructor
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config = null) {
        $this->config = $config;
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
            case self::BREW:
                return true;
            default:
                return false;
        }
    }

    /**
     * Generate an operation object based on some parameters
     *
     * @param string $resource The accessed resource
     * @param string $method The HTTP method
     * @param string $imageIdentifier Optional Image identifier
     * @param string $extra Optional extra argument
     * @throws PHPIMS\Exception
     * @return PHPIMS\OperationInterface
     */
    private function resolveOperation($resource, $method, $imageIdentifier = null, $extra = null) {
        $operation = null;

        if ($method === self::GET && $imageIdentifier) {
            if ($extra === 'meta') {
                $operation = 'PHPIMS\\Operation\\GetImageMetadata';
            } else {
                $operation = 'PHPIMS\\Operation\\GetImage';
            }
        } else if ($method === self::POST && $imageIdentifier) {
            if ($extra === 'meta') {
                $operation = 'PHPIMS\\Operation\\EditImageMetadata';
            } else {
                $operation = 'PHPIMS\\Operation\\AddImage';
            }
        } else if ($method === self::DELETE && $imageIdentifier) {
            if ($extra === 'meta') {
                $operation = 'PHPIMS\\Operation\\DeleteImageMetadata';
            } else {
                $operation = 'PHPIMS\\Operation\\DeleteImage';
            }
        } else if ($method === self::BREW) {
            throw new Exception('I\'m a teapot!', 418);
        }

        if ($operation === null) {
            throw new Exception('Unsupported operation', 400);
        }

        return Operation::factory($operation, $resource, $method, $imageIdentifier);
    }

    /**
     * Handle a request
     *
     * @param string $resource The resource accessed
     * @param string $method The HTTP method (one of the defined constants)
     * @throws PHPIMS\Exception
     */
    public function handle($resource, $method) {
        if (!self::isValidMethod($method)) {
            throw new Exception($method . ' not implemented', 501);
        }

        // Trim away slashes
        $resource = trim($resource, '/');
        $matches  = array();

        // See if
        if (!preg_match('#(images|(?<imageIdentifier>[a-f0-9]{32}\.[a-zA-Z]{3,4})(?:/(?<extra>meta))?)$#', $resource, $matches)) {
            throw new Exception('Unknown resource', 400);
        }

        $imageIdentifier = isset($matches['imageIdentifier']) ? $matches['imageIdentifier'] : null;
        $extra = isset($matches['extra']) ? $matches['extra'] : null;

        $operation = $this->resolveOperation($resource, $method, $imageIdentifier, $extra);
        $operation->init($this->config)
                  ->preExec()
                  ->exec()
                  ->postExec();

        return $operation->getResponse();
    }
}