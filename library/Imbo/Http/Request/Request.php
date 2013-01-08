<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Request;

use Imbo\Http\ParameterContainer,
    Imbo\Http\ServerContainer,
    Imbo\Http\HeaderContainer,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Image\Image;

/**
 * Request class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
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
     * @var ParameterContainer
     */
    private $query;

    /**
     * Request data
     *
     * @var ParameterContainer
     */
    private $request;

    /**
     * Server data
     *
     * @var ServerContainer
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
     * Image instance
     *
     * @var Image
     */
    private $image;

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
     * Array of transformations
     *
     * @var array
     */
    private $transformations;

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
    public function setImage(Image $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage() {
        return $this->image;
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
        if ($this->transformations === null) {
            $this->transformations = array();

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

                $this->transformations[] = array(
                    'name'   => $name,
                    'params' => $params,
                );
            }
        }

        return $this->transformations;
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
