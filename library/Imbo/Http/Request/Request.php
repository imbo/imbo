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
     * @var ParameterContainerInterface
     */
    private $query;

    /**
     * Request data
     *
     * @var ParameterContainerInterface
     */
    private $request;

    /**
     * Server data
     *
     * @var ServerContainerInterface
     */
    private $server;

    /**
     * HTTP headers
     *
     * @var HeaderContainer
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
     * Chain of image transformations
     *
     * @var TransformationChain
     */
    private $transformationChain;

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
     * {@inheritdoc}
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setPublicKey($key) {
        $this->publicKey = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrivateKey($key) {
        $this->privateKey = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformations() {
        if ($this->transformationChain === null) {
            $this->transformationChain = new TransformationChain();
            $transformations = $this->query->get('t', array());

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

                // Lowercase the name
                $name = strtolower($name);

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
                    $this->transformationChain->border($p('color'), $p('width'), $p('height'));
                } else if ($name === 'compress') {
                    $this->transformationChain->compress($p('quality'));
                } else if ($name === 'crop') {
                    $this->transformationChain->crop($p('x'), $p('y'), $p('width'), $p('height'));
                } else if ($name === 'fliphorizontally') {
                    $this->transformationChain->flipHorizontally();
                } else if ($name === 'flipvertically') {
                    $this->transformationChain->flipVertically();
                } else if ($name === 'maxsize') {
                    $this->transformationChain->maxSize($p('width'), $p('height'));
                } else if ($name === 'resize') {
                    $this->transformationChain->resize($p('width'), $p('height'));
                } else if ($name === 'rotate') {
                    $this->transformationChain->rotate($p('angle'), $p('bg'));
                } else if ($name === 'thumbnail') {
                    $this->transformationChain->thumbnail($p('width'), $p('height'), $p('fit'));
                } else if ($name === 'canvas') {
                    $this->transformationChain->canvas($p('width'), $p('height'), $p('mode'), $p('x'), $p('y'), $p('bg'));
                } else if ($name == 'transpose') {
                    $this->transformationChain->transpose();
                } else if ($name == 'transverse') {
                    $this->transformationChain->transverse();
                } else {
                    throw new InvalidArgumentException('Invalid transformation: ' . $name, 400);
                }
            }
        }

        return $this->transformationChain;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTransformations() {
        return $this->getExtension() || $this->getQuery()->has('t');
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme() {
        $https = strtolower($this->server->get('HTTPS'));

        return ($https === 'on' || $https == 1) ? 'https' : 'http';
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getPort() {
        return $this->server->get('SERVER_PORT');
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseUrl() {
        return $this->baseUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getImageIdentifier() {
        return $this->imageIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function setImageIdentifier($imageIdentifier) {
        $this->imageIdentifier = $imageIdentifier;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRealImageIdentifier() {
        if ($this->rawData === null) {
            return null;
        }

        return md5($this->rawData);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtension($extension) {
        $this->extension = $extension;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod() {
        return $this->server->get('REQUEST_METHOD');
    }

    /**
     * {@inheritdoc}
     */
    public function getRawData() {
        if ($this->rawData === null) {
            $this->rawData = file_get_contents('php://input');
        }

        return $this->rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function setRawData($data) {
        $this->rawData = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function getServer() {
        return $this->server;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnsafe() {
        $method = $this->getMethod();

        return $method === RequestInterface::METHOD_POST ||
               $method === RequestInterface::METHOD_PUT ||
               $method === RequestInterface::METHOD_DELETE;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getAcceptableContentTypes() {
        return $this->splitAcceptHeader($this->headers->get('Accept'));
    }

    /**
     * {@inheritdoc}
     */
    public function setResource($resource) {
        $this->resource = $resource;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource() {
        return $this->resource;
    }
}
