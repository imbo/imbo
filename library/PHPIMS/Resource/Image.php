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
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource;

use PHPIMS\Http\Request\RequestInterface;
use PHPIMS\Http\Response\ResponseInterface;
use PHPIMS\Database\DatabaseInterface;
use PHPIMS\Storage\StorageInterface;
use PHPIMS\Image\Image as ImageObject;
use PHPIMS\Image\Exception as ImageException;
use PHPIMS\Image\ImageInterface;
use PHPIMS\Image\ImageIdentification;
use PHPIMS\Image\ImageIdentificationInterface;
use PHPIMS\Database\Exception as DatabaseException;
use PHPIMS\Storage\Exception as StorageException;

/**
 * Image resource
 *
 * @package PHPIMS
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Image extends Resource implements ResourceInterface {
    /**
     * Image for the client
     *
     * @var PHPIMS\Image\ImageInterface
     */
    private $image;

    /**
     * Image identification instance
     *
     * @var PHPIMS\Image\ImageIdentification
     */
    private $imageIdentification;

    /**
     * Class constructor
     *
     * @param PHPIMS\Image\ImageInterface $image An image instance
     * @param PHPIMS\Image\ImageIdentification $imageIdentification An image identification instance
     */
    public function __construct(ImageInterface $image = null, ImageIdentificationInterface $imageIdentification = null) {
        if ($image === null) {
            $image = new ImageObject();
        }

        if ($imageIdentification === null) {
            $imageIdentification = new ImageIdentification();
        }

        $this->image = $image;
        $this->imageIdentification = $imageIdentification;
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::getAllowedMethods()
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
     * @see PHPIMS\Resource\ResourceInterface::put()
     */
    public function put(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        // Prepare the image instance
        $this->prepareImage($request);

        // Identify the image to set the correct mime type and extension in the image instance
        try {
            $this->imageIdentification->identifyImage($this->image);
        } catch (ImageException $e) {
            throw new Exception('Could not identify the image', 500);
        }

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        // Make sure that the extension of the file along with the PUT is correct
        $imageIdentifier = substr($imageIdentifier, 0, 32) . '.' . $this->image->getExtension();

        // Insert image to the database
        try {
            $database->insertImage($publicKey, $imageIdentifier, $this->image);
        } catch (DatabaseException $e) {
            throw new Exception('Database error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        // Store the image
        try {
            $storage->store($publicKey, $imageIdentifier, $this->image);
        } catch (StorageException $e) {
            throw new Exception('Storage error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        // Populate the response object
        $response->setStatusCode(201)
                 ->setBody(array('imageIdentifier' => $imageIdentifier));
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::delete()
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        try {
            $database->deleteImage($publicKey, $imageIdentifier);
        } catch (DatabaseException $e) {
            throw new Exception('Database error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        try {
            $storage->delete($publicKey, $imageIdentifier);
        } catch (StorageException $e) {
            throw new Exception('Storage error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        // Fetch information from the database
        try {
            $database->load($publicKey, $imageIdentifier, $this->image);
        } catch (DatabaseError $e) {
            throw new Exception('Database error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        // Load the image
        try {
            $storage->load($publicKey, $imageIdentifier, $this->image);
        } catch (DatabaseException $e) {
            throw new Exception('Storage error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        // Identify the image to set the correct mime type and extension in the image instance
        try {
            $this->imageIdentification->identifyImage($this->image);
        } catch (ImageException $e) {
            throw new Exception('Could not identify the image', 500);
        }

        // Add the content type of the image to the response headers
        $response->getHeaders()->set('Content-Type', $this->image->getMimeType());

        // Apply transformations
        $transformationChain = $request->getTransformations();

        try {
            $transformationChain->applyToImage($this->image);
        } catch (TransformationException $e) {
            throw new Exception('Transformation failed with message: ' . $e->getMessage(), 401, $e);
        }

        // Store the image in the response
        $response->setBody($this->image);
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::head()
     */
    public function head(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        // Fetch information from the database
        try {
            $database->load($request->getPublicKey(), $request->getImageIdentifier(), $this->image);
        } catch (DatabaseError $e) {
            throw new Exception('Database error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $response->setContentType($this->image->getMimeType());
    }

    /**
     * Prepare the local image property when someone PUT's a new image to the server
     *
     * @param PHPIMS\Http\Request\RequestInterface $request The current request where the raw image
     *                                                      data is accessible
     * @throws PHPIMS\Resource\Exception
     */
    private function prepareImage(RequestInterface $request) {
        // Fetch image data from input
        $imageBlob = $request->getRawData();

        if (empty($imageBlob)) {
            throw new Exception('No image attached', 400);
        }

        // Calculate hash
        $actualHash = md5($imageBlob);

        // Get image identifier from request
        $imageIdentifier = $request->getImageIdentifier();

        if ($actualHash !== substr($imageIdentifier, 0, 32)) {
            throw new Exception('Hash mismatch', 400);
        }

        // Store file to disk and use getimagesize() to fetch width/height
        $tmpFile = tempnam(sys_get_temp_dir(), 'PHPIMS_uploaded_image');
        file_put_contents($tmpFile, $imageBlob);
        $size = getimagesize($tmpFile);

        // Fetch the image object and store the blob
        $this->image->setBlob($imageBlob)
                    ->setWidth($size[0])
                    ->setHeight($size[1]);

        unlink($tmpFile);
    }
}
