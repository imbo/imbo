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
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Resource;

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface,
    Imbo\Image\Image as ImageObject,
    Imbo\Image\ImageInterface,
    Imbo\Image\ImagePreparation,
    Imbo\Image\ImagePreparationInterface,
    Imbo\Exception\StorageException,
    Imbo\Exception\ResourceException,
    Imbo\Image\Transformation\Convert,
    Imbo\Http\ContentNegotiation;

/**
 * Image resource
 *
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Image extends Resource implements ResourceInterface {
    /**
     * Image for the client
     *
     * @var ImageInterface
     */
    private $image;

    /**
     * Image prepation instance
     *
     * @var ImagePreparation
     */
    private $imagePreparation;

    /**
     * Content negotiation instance
     *
     * @var ContentNegotiation
     */
    private $contentNegotiation;

    /**
     * Class constructor
     *
     * @param ImageInterface $image An image instance
     * @param ImagePreparationInterface $imagePreparation An image preparation instance
     * @param ContentNegotiation $contentNegotiation Content negotiation instance
     */
    public function __construct(ImageInterface $image = null, ImagePreparationInterface $imagePreparation = null, ContentNegotiation $contentNegotiation = null) {
        if ($image === null) {
            $image = new ImageObject();
        }

        if ($imagePreparation === null) {
            $imagePreparation = new ImagePreparation();
        }

        if ($contentNegotiation === null) {
            $contentNegotiation = new ContentNegotiation();
        }

        $this->image = $image;
        $this->imagePreparation = $imagePreparation;
        $this->contentNegotiation = $contentNegotiation;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function put(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        // Prepare the image based on the input stream in the request
        $this->imagePreparation->prepareImage($request, $this->image);
        $this->eventManager->trigger('image.put.imagepreparation.post');

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getRealImageIdentifier();

        // Insert the image to the database
        $database->insertImage($publicKey, $imageIdentifier, $this->image);

        // Store the image
        try {
            $storage->store($publicKey, $imageIdentifier, $this->image->getBlob());
        } catch (StorageException $e) {
            // Remove image from the database
            $database->deleteImage($publicKey, $imageIdentifier);

            throw $e;
        }

        $response->setStatusCode(201)
                 ->setBody(array('imageIdentifier' => $imageIdentifier));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        $database->deleteImage($publicKey, $imageIdentifier);
        $storage->delete($publicKey, $imageIdentifier);

        $response->setBody(array(
            'imageIdentifier' => $imageIdentifier,
        ));
    }

    /**
     * {@inheritdoc}
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
        $etag = '"' . md5($publicKey . $imageIdentifier . $serverContainer->get('REQUEST_URI')) . '"';

        // Fetch formatted last modified timestamp from the storage driver
        $lastModified = $this->formatDate($storage->getLastModified($publicKey, $imageIdentifier));

        // Add the ETag to the response headers
        $responseHeaders->set('ETag', $etag);

        if (
            $lastModified === $requestHeaders->get('if-modified-since') &&
            $etag === $requestHeaders->get('if-none-match')
        ) {
            $response->setNotModified();
            return;
        }

        // Fetch the image data and store the data in the image instance
        $imageData = $storage->getImage($publicKey, $imageIdentifier);
        $this->image->setBlob($imageData);

        // Set some response headers before we apply optional transformations
        $responseHeaders
            // Set the last modification date
            ->set('Last-Modified', $lastModified)

            // Set the max-age to a year since the image never changes
            ->set('Cache-Control', 'max-age=31536000')

            // Custom Imbo headers
            ->set('X-Imbo-OriginalMimeType', $this->image->getMimeType())
            ->set('X-Imbo-OriginalWidth', $this->image->getWidth())
            ->set('X-Imbo-OriginalHeight', $this->image->getHeight())
            ->set('X-Imbo-OriginalFileSize', $this->image->getFilesize());

        // Apply transformations
        $transformationChain = $request->getTransformations();
        $transformationChain->applyToImage($this->image);

        // Fetch the requested resource to see if we have to convert the image
        $path = $request->getPath();
        $resource = substr($path, strrpos($path, '/') + 1);

        if (isset($resource[32])) {
            // We have a requested image type
            $extension = substr($resource, 33);

            $convert = new Convert($extension);
            $convert->applyToImage($this->image);
        }

        // If the image type is not accepted by the client generate an error
        if (!$this->contentNegotiation->isAcceptable($this->image->getMimetype(), $request->getAcceptableContentTypes())) {
            throw new ResourceException('Not Acceptable', 406);
        }

        // Set the content length and content-type after transformations have been applied
        $imageData = $this->image->getBlob();
        $responseHeaders->set('Content-Length', strlen($imageData))
                        ->set('Content-Type', $this->image->getMimeType());

        $response->setBody($imageData);
    }

    /**
     * {@inheritdoc}
     */
    public function head(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $this->get($request, $response, $database, $storage);

        // Remove body from the response, but keep everything else
        $response->setBody(null);
    }
}
