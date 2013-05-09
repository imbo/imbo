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

use Imbo\Exception\InvalidArgumentException,
    Imbo\Model\Image,
    Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Request class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class Request extends SymfonyRequest {
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
     * The current extension (if any)
     *
     * @var string
     */
    private $extension;

    /**
     * The currently requested resource name (as defined by the constants in
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
     * {@inheritdoc}
     */
    public static function createFromGlobals()
    {
        $_SERVER['QUERY_STRING'] = str_replace('t%5B%5D=', 't[]=', $_SERVER['QUERY_STRING']);

        return parent::createFromGlobals();
    }

    /**
     * Set an image model
     *
     * @param Image $image An image model instance
     * @return Request
     */
    public function setImage(Image $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * Get an image model attached to the request (on PUT)
     *
     * @return null|Image
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Get the public key found in the request
     *
     * @return string
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * Set the public key
     *
     * @param string $key The key to set
     * @return Request
     */
    public function setPublicKey($key) {
        $this->publicKey = $key;

        return $this;
    }

    /**
     * Get the private key
     *
     * The private key property is populated by the server based on the public key from the
     * request. The client itself does not place the private key in the request.
     *
     * @return string
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * Set the private key
     *
     * @param string $key The key to set
     * @return Request
     */
    public function setPrivateKey($key) {
        $this->privateKey = $key;

        return $this;
    }

    /**
     * Get image transformations from the request
     *
     * @return array
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
     * Check whether or not the request includes image transformations
     *
     * @return boolean
     */
    public function hasTransformations() {
        return $this->getExtension() || $this->query->has('t');
    }

    /**
     * Get the image identifier from the URL
     *
     * @return string|null
     */
    public function getImageIdentifier() {
        return $this->imageIdentifier;
    }

    /**
     * Set the image identifier
     *
     * @param string $imageIdentifier The image identifier to set
     * @return Request
     */
    public function setImageIdentifier($imageIdentifier) {
        $this->imageIdentifier = $imageIdentifier;

        return $this;
    }

    /**
     * Get the current requested extension (if any)
     *
     * @return string|null
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * Set the extension requested
     *
     * @param string $extension The extension to set
     * @return Request
     */
    public function setExtension($extension) {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Set the resource name (one of the constants defined in Imbo\Resource\ResourceInterface)
     *
     * @param string $resource The name of the resource
     * @return Request
     */
    public function setResource($resource) {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get the resource name
     *
     * @return string
     */
    public function getResource() {
        return $this->resource;
    }

    /**
     * Get the URI without the Symfony normalization applied to the query string
     *
     * @return string
     */
    public function getRawUri() {
        $query = $this->server->get('QUERY_STRING');

        if (!empty($query)) {
            $query = '?' . $query;
        }

        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $query;
    }
}
