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
 * @package Imbo
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Resource;

use Imbo\Exception as ImboException;
use Imbo\Http\Request\RequestInterface;
use Imbo\Http\Response\ResponseInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;
use Imbo\Image\Image as ImageObject;
use Imbo\Image\Exception as ImageException;
use Imbo\Image\ImageInterface;
use Imbo\Image\ImageIdentification;
use Imbo\Image\ImageIdentificationInterface;
use Imbo\Image\ImagePreparation;
use Imbo\Image\ImagePreparationInterface;
use Imbo\Database\Exception as DatabaseException;

/**
 * Image resource
 *
 * @package Imbo
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Image extends Resource implements ResourceInterface {
    /**
     * Image for the client
     *
     * @var Imbo\Image\ImageInterface
     */
    private $image;

    /**
     * Image identification instance
     *
     * @var Imbo\Image\ImageIdentification
     */
    private $imageIdentification;

    /**
     * Image prepation instance
     *
     * @var Imbo\Image\ImagePreparation
     */
    private $imagePreparation;

    /**
     * Class constructor
     *
     * @param Imbo\Image\ImageInterface $image An image instance
     * @param Imbo\Image\ImageIdentificationInterface $imageIdentification An image identification instance
     * @param Imbo\Image\ImagePreparationInterface $imagePreparation An image preparation instance
     */
    public function __construct(ImageInterface $image = null, ImageIdentificationInterface $imageIdentification = null, ImagePreparationInterface $imagePreparation = null) {
        if ($image === null) {
            $image = new ImageObject();
        }

        if ($imageIdentification === null) {
            $imageIdentification = new ImageIdentification();
        }

        if ($imagePreparation === null) {
            $imagePreparation = new ImagePreparation();
        }

        $this->image = $image;
        $this->imageIdentification = $imageIdentification;
        $this->imagePreparation = $imagePreparation;
    }

    /**
     * @see Imbo\Resource\ResourceInterface::getAllowedMethods()
     */
    public function getAllowedMethods() {
        return array(
            RequestInterface::METHOD_GET,
            RequestInterface::METHOD_HEAD,
            RequestInterface::METHOD_DELETE,
            RequestInterface::METHOD_PUT,
        );
    }

    /**
     * @see Imbo\Resource\ResourceInterface::put()
     */
    public function put(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        try {
            // Prepare the image based on the input stream in the request
            $this->imagePreparation->prepareImage($request, $this->image);

            // Identify the image to set the correct mime type and extension in the image instance
            $this->imageIdentification->identifyImage($this->image);
        } catch (ImageException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        // Make sure that the extension of the file along with the PUT is correct
        $imageIdentifier = substr($imageIdentifier, 0, 32) . '.' . $this->image->getExtension();

        try {
            // Insert the image to the database
            $database->insertImage($publicKey, $imageIdentifier, $this->image);

            // Store the image
            $storage->store($publicKey, $imageIdentifier, $this->image);
        } catch (ImboException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        // Populate the response object
        $response->setStatusCode(201)
                 ->setBody(array('imageIdentifier' => $imageIdentifier));
    }

    /**
     * @see Imbo\Resource\ResourceInterface::delete()
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        try {
            $database->deleteImage($publicKey, $imageIdentifier);
            $storage->delete($publicKey, $imageIdentifier);
        } catch (ImboException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @see Imbo\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        // Fetch some entries from the request and the respones
        $publicKey       = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $serverContainer = $request->getServer();
        $requestHeaders  = $request->getHeaders();
        $responseHeaders = $response->getHeaders();

        // Generate ETag using public key, image identifier and the query string from the
        // request
        $etag = md5($publicKey . $imageIdentifier . $serverContainer->get('REDIRECT_QUERY_STRING'));

        try {
            // Fetch information from the database (injects mime type, width and height to the
            // image instance)
            $database->load($publicKey, $imageIdentifier, $this->image);
            $mimeType = $this->image->getMimeType();

            // Set the image extension based on the mime type
            $this->image->setExtension(ImageIdentification::$mimeTypes[$mimeType]);

            // Fetch last modified timestamp from the storage driver
            $lastModified = date('r', $storage->getLastModified($publicKey, $imageIdentifier));

            if (
                $lastModified === $requestHeaders->get('if-modified-since') &&
                $etag === $requestHeaders->get('if-none-match')
            ) {
                $response->setStatusCode(304);
                return;
            }

            // Load the image data (injects the blob into the image instance)
            $storage->load($publicKey, $imageIdentifier, $this->image);

            // Set some response headers
            $responseHeaders->set('Last-Modified', $lastModified)
                            ->set('ETag', $etag)
                            ->set('Content-Type', $mimeType)
                            ->set('X-Imbo-OriginalWidth', $this->image->getWidth())
                            ->set('X-Imbo-OriginalHeight', $this->image->getHeight())
                            ->set('X-Imbo-OriginalFileSize', $this->image->getFileSize());

            // Apply transformations
            $transformationChain = $request->getTransformations();
            $transformationChain->applyToImage($this->image);
        } catch (ImboException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        // Store the image in the response
        $response->setBody($this->image);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::head()
     */
    public function head(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        return $this->get($request, $response, $database, $storage);
    }
}
