<?php
/**
 * Imbo
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
 * @package Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Http\Request;

use Imbo\Http\ParameterContainer;
use Imbo\Http\ServerContainer;
use Imbo\Http\HeaderContainer;
use Imbo\Image\Transformation;
use Imbo\Image\TransformationChain;

/**
 * Request class
 *
 * @package Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Request implements RequestInterface {
    /**
     * The current accessed path
     *
     * @var string
     */
    private $path;

    /**
     * Query data
     *
     * @var Imbo\Http\ParameterContainerInterface
     */
    private $query;

    /**
     * Request data
     *
     * @var Imbo\Http\ParameterContainerInterface
     */
    private $request;

    /**
     * Server data
     *
     * @var Imbo\Http\ServerContainerInterface
     */
    private $server;

    /**
     * HTTP headers
     *
     * @var Imbo\Http\HeaderContainer
     */
    private $headers;

    /**
     * The public key from the request
     *
     * @var string
     */
    private $publicKey;

    /**
     * The current resource (excluding the public key)
     *
     * Examples:
     * * images
     * * <image identifier>
     * * <image identifier>/meta
     *
     * @var string
     */
    private $resource;

    /**
     * The current image identifier
     *
     * @var string
     */
    private $imageIdentifier;

    /**
     * Class constructor
     *
     * @param array $query Query data ($_GET)
     * @param array $request Request data ($_POST)
     * @param array $server Server data ($_SERVER)
     */
    public function __construct(array $query = array(), array $request = array(), array $server = array()) {
        $this->query   = new ParameterContainer($query);
        $this->request = new ParameterContainer($request);
        $this->server  = new ServerContainer($server);
        $this->headers = new HeaderContainer($this->server->getHeaders());

        // Remove a possible prefix in the URL
        $excessDir = str_replace(rtrim($this->server->get('DOCUMENT_ROOT'), '/'), '', dirname($this->server->get('SCRIPT_FILENAME')));
        $resource  = str_replace($excessDir, '', $this->server->get('REDIRECT_URL'));

        $parts = parse_url($resource);
        $this->path = $parts['path'];
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getPublicKey()
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::setPublicKey()
     */
    public function setPublicKey($key) {
        $this->publicKey = $key;

        return $this;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getTransformations()
     */
    public function getTransformations() {
        $transformations = $this->query->get('t', array());
        $chain = new TransformationChain();

        foreach ($transformations as $transformation) {
            // See if the transformation has any parameters
            $pos = strpos($transformation, ':');
            $urlParams = '';

            if ($pos === false) {
                // No params exist
                $name = $transformation;
            } else {
                list($name, $urlParams) = explode(':', $transformation, 2);
            }

            // Initialize params for the transformation
            $params = array();

            // See if we have more than one parameter
            if (strpos($urlParams, ',') !== false) {
                $urlParams = explode(',', $urlParams);
            } else {
                $urlParams = array($urlParams);
            }

            foreach ($urlParams as $param) {
                $pos = strpos($param, '=');

                if ($pos !== false) {
                    $params[substr($param, 0, $pos)] = substr($param, $pos + 1);
                }
            }

            // Closure to help fetch parameters
            $p = function($key) use ($params) {
                return isset($params[$key]) ? $params[$key] : null;
            };

            if ($name === 'border') {
                $chain->add(new Transformation\Border($p('color'), $p('width'), $p('height')));
            } else if ($name === 'compress') {
                $chain->add(new Transformation\Compress($p('quality')));
            } else if ($name === 'crop') {
                $chain->add(new Transformation\Crop($p('x'), $p('y'), $p('width'), $p('height')));
            } else if ($name === 'flipHorizontally') {
                $chain->add(new Transformation\FlipHorizontally());
            } else if ($name === 'flipVertically') {
                $chain->add(new Transformation\FlipVertically());
            } else if ($name === 'resize') {
                $chain->add(new Transformation\Resize($p('width'), $p('height')));
            } else if ($name === 'rotate') {
                $chain->add(new Transformation\Rotate($p('angle'), $p('bg')));
            } else if ($name === 'thumbnail') {
                $chain->add(new Transformation\Thumbnail($p('width'), $p('height'), $p('fit')));
            }
        }

        return $chain;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getScheme()
     */
    public function getScheme() {
        $https = strtolower($this->server->get('HTTPS'));

        return ($https === 'on' || $https == 1) ? 'https' : 'http';
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getHost()
     */
    public function getHost() {
        return $this->server->get('SERVER_NAME');
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getPath()
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getUrl()
     */
    public function getUrl() {
        return sprintf('%s://%s%s', $this->getScheme(), $this->getHost(), $this->getPath());
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getImageIdentifier()
     */
    public function getImageIdentifier() {
        return $this->imageIdentifier;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::setImageIdentifier()
     */
    public function setImageIdentifier($imageIdentifier) {
        $this->imageIdentifier = $imageIdentifier;

        return $this;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getMethod()
     */
    public function getMethod() {
        return $this->server->get('REQUEST_METHOD');
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getRawData()
     * @codeCoverageIgnore
     */
    public function getRawData() {
        return file_get_contents('php://input');
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getQuery()
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getRequest()
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getServer()
     */
    public function getServer() {
        return $this->server;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getHeaders()
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::isUnsafe()
     */
    public function isUnsafe() {
        $method = $this->getMethod();

        return $method === RequestInterface::METHOD_POST ||
               $method === RequestInterface::METHOD_PUT ||
               $method === RequestInterface::METHOD_DELETE;
    }
}
