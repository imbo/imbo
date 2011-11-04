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
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Resource;

use Imbo\Http\Request\RequestInterface;
use Imbo\Http\Response\ResponseInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;
use Imbo\Image\Image as ImageObject;
use Imbo\Image\ImageInterface;
use Imbo\Image\ImagePreparation;
use Imbo\Image\ImagePreparationInterface;
use Imbo\Storage\Exception as StorageException;
use Imbo\Image\Transformation\Convert;

/**
 * Image resource
 *
 * @package Resources
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
     * Image prepation instance
     *
     * @var Imbo\Image\ImagePreparation
     */
    private $imagePreparation;

    /**
     * Class constructor
     *
     * @param Imbo\Image\ImageInterface $image An image instance
     * @param Imbo\Image\ImagePreparationInterface $imagePreparation An image preparation instance
     */
    public function __construct(ImageInterface $image = null, ImagePreparationInterface $imagePreparation = null) {
        if ($image === null) {
            $image = new ImageObject();
        }

        if ($imagePreparation === null) {
            $imagePreparation = new ImagePreparation();
        }

        $this->image = $image;
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
        // Prepare the image based on the input stream in the request
        $this->imagePreparation->prepareImage($request, $this->image);

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        // Insert the image to the database
        $database->insertImage($publicKey, $imageIdentifier, $this->image);

        // Store the image
        try {
            $storage->store($publicKey, $imageIdentifier, $this->image);
        } catch (StorageException $e) {
            // Remove image from the database
            $database->deleteImage($publicKey, $imageIdentifier);

            throw $e;
        }

        $response->setStatusCode(201);

        $this->getResponseWriter()->write(array('imageIdentifier' => $imageIdentifier), $request, $response);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::delete()
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        $database->deleteImage($publicKey, $imageIdentifier);
        $storage->delete($publicKey, $imageIdentifier);

        $this->getResponseWriter()->write(array('imageIdentifier' => $imageIdentifier), $request, $response);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey       = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $serverContainer = $request->getServer();
        $requestHeaders  = $request->getHeaders();
        $responseHeaders = $response->getHeaders();

        // Fetch information from the database (injects mime type, width and height to the
        // image instance)
        $database->load($publicKey, $imageIdentifier, $this->image);

        // Generate ETag using public key, image identifier, and the redirect url
        $etag = md5($publicKey . $imageIdentifier . $serverContainer->get('REQUEST_URI'));

        // Fetch last modified timestamp from the storage driver
        $lastModified = date('r', $storage->getLastModified($publicKey, $imageIdentifier));

        if (
            $lastModified === $requestHeaders->get('if-modified-since') &&
            $etag === trim($requestHeaders->get('if-none-match'), '"')
        ) {
            $response->setNotModified();
            return;
        }

        // Load the image data (injects the blob into the image instance)
        $storage->load($publicKey, $imageIdentifier, $this->image);

        // Fetch the requested resource to see if we have to convert the image
        $resource = $request->getResource();
        $originalMimeType = $this->image->getMimeType();
        $originalFilesize = $this->image->getFilesize();

        if (isset($resource[32])) {
            // We have a requested image type
            $extension = substr($resource, 33);

            $convert = new Convert($extension);
            $convert->applyToImage($this->image);
        }

        // Set some response headers before we apply optional transformations
        $responseHeaders->set('Last-Modified', $lastModified)
                        ->set('ETag', '"' . $etag . '"')
                        ->set('Content-Type', $this->image->getMimeType())
                        ->set('X-Imbo-OriginalMimeType', $originalMimeType)
                        ->set('X-Imbo-OriginalWidth', $this->image->getWidth())
                        ->set('X-Imbo-OriginalHeight', $this->image->getHeight())
                        ->set('X-Imbo-OriginalFileSize', $originalFilesize);

        // Apply transformations
        $transformationChain = $request->getTransformations();
        $transformationChain->applyToImage($this->image);

        // Set the content length after transformations have been applied
        $imageData = $this->image->getBlob();
        $responseHeaders->set('Content-Length', strlen($imageData));

        $response->setBody($imageData);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::head()
     */
    public function head(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $this->get($request, $response, $database, $storage);

        // Remove body from the response, but keep everything else
        $response->setBody(null);
    }
}
