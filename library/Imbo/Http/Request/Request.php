<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Http\Request;

use Imbo\Http\ParameterContainer,
    Imbo\Http\ServerContainer,
    Imbo\Http\HeaderContainer,
    Imbo\Image\Transformation,
    Imbo\Image\TransformationChain,
    Imbo\Exception\InvalidArgumentException;

/**
 * Request class
 *
 * @package Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
     * The private key
     *
     * @var string
     */
    private $privateKey;

    /**
     * The current image identifier (if any)
     *
     * @var string
     */
    private $imageIdentifier;

    /**
     * Raw image data
     *
     * @var string
     */
    private $rawData;

    /**
     * The current extension (if any)
     *
     * @var string
     */
    private $extension;

    /**
     * The currently requested resorce name (as defined by the constants in
     * Imbo\Resource\ResourceInterface).
     *
     * @var string
     */
    private $resource;

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

        $this->baseUrl = str_replace(rtrim($this->server->get('DOCUMENT_ROOT'), '/'), '', dirname($this->server->get('SCRIPT_FILENAME')));
        $this->path = str_replace($this->baseUrl, '', $this->server->get('REQUEST_URI'));

        if (strpos($this->path, '?') !== false) {
            $this->path = substr($this->path, 0, strpos($this->path, '?'));
        }
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
     * @see Imbo\Http\Request\RequestInterface::getPrivateKey()
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::setPrivateKey()
     */
    public function setPrivateKey($key) {
        $this->privateKey = $key;

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
                $chain->border($p('color'), $p('width'), $p('height'));
            } else if ($name === 'compress') {
                $chain->compress($p('quality'));
            } else if ($name === 'crop') {
                $chain->crop($p('x'), $p('y'), $p('width'), $p('height'));
            } else if ($name === 'flipHorizontally') {
                $chain->flipHorizontally();
            } else if ($name === 'flipVertically') {
                $chain->flipVertically();
            } else if ($name === 'maxSize') {
                $chain->maxSize($p('width'), $p('height'));
            } else if ($name === 'resize') {
                $chain->resize($p('width'), $p('height'));
            } else if ($name === 'rotate') {
                $chain->rotate($p('angle'), $p('bg'));
            } else if ($name === 'thumbnail') {
                $chain->thumbnail($p('width'), $p('height'), $p('fit'));
            } else if ($name === 'canvas') {
                $chain->canvas($p('width'), $p('height'), $p('mode'), $p('x'), $p('y'), $p('bg'));
            } else if ($name == 'transpose') {
                $chain->transpose();
            } else if ($name == 'transverse') {
                $chain->transverse();
            } else {
                throw new InvalidArgumentException('Invalid transformation: ' . $name, 400);
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
        $host = $this->server->get('HTTP_HOST');

        // Remove optional port
        if (($pos = strpos($host, ':')) !== false) {
            $host = substr($host, 0, $pos);
        }

        return $host;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getPort()
     */
    public function getPort() {
        return $this->server->get('SERVER_PORT');
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getBaseUrl()
     */
    public function getBaseUrl() {
        return $this->baseUrl;
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
        $port = (int) $this->getPort();
        $scheme = $this->getScheme();

        if (
            !$port ||
            ($scheme === 'http' && $port === 80) ||
            ($scheme === 'https' && $port === 443)
        ) {
            $port = '';
        } else if ($port) {
            $port = ':' . $port;
        }

        // Fetch query string
        $queryString = $this->getQuery()->asString();

        if (!empty($queryString)) {
            $queryString = '?' . $queryString;
        }

        $url = sprintf('%s://%s%s%s%s%s', $scheme, $this->getHost(), $port, $this->getBaseUrl(), $this->getPath(), $queryString);

        return $url;
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
     * @see Imbo\Http\Request\RequestInterface::getRealImageIdentifier()
     */
    public function getRealImageIdentifier() {
        if ($this->rawData === null) {
            return null;
        }

        return md5($this->rawData);
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getExtension()
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::setExtension()
     */
    public function setExtension($extension) {
        $this->extension = $extension;

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
     */
    public function getRawData() {
        if ($this->rawData === null) {
            $this->rawData = file_get_contents('php://input');
        }

        return $this->rawData;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::setRawData()
     */
    public function setRawData($data) {
        $this->rawData = $data;

        return $this;
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

    /**
     * @see Imbo\Http\Request\RequestInterface::splitAcceptHeader()
     */
    public function splitAcceptHeader($header) {
        if (!$header) {
            return array();
        }

        $values = array();

        // Explode on , to get all media types
        $mediaTypes = array_map('trim', explode(',', $header));

        // Remove possible empty values due to poorly formatted headers
        $mediaTypes = array_filter($mediaTypes);

        foreach ($mediaTypes as $type) {
            $quality = 1;

            if (preg_match('/;\s*q=(\d\.?\d?)/', $type, $match)) {
                $quality = (float) $match[1];

                // Remove the matched string from the type
                $type = substr($type, 0, -strlen($match[0]));
            }

            if ($quality) {
                $values[$type] = $quality;
            }
        }

        // Increase all quality values to be able to get a correct sort
        $f = .00001;
        $i = 0;

        $values = array_reverse($values);
        $factor = array();

        foreach ($values as $type => $q) {
            $values[$type] += ($f * ++$i);
            $factor[$type] = $i;
        }

        // Sort the values and maintain key association
        arsort($values);

        // Decrease the values back to the original values
        foreach ($values as $type => $q) {
            $values[$type] -= $f * $factor[$type];
        }

        return $values;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getAcceptableContentTypes()
     */
    public function getAcceptableContentTypes() {
        return $this->splitAcceptHeader($this->headers->get('Accept'));
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::setResource()
     */
    public function setResource($resource) {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @see Imbo\Http\Request\RequestInterface::getResource()
     */
    public function getResource() {
        return $this->resource;
    }
}
