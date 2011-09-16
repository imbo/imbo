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
     * Supported mime types and the correct file extension
     *
     * @var array
     */
    static public $mimeTypes = array(
        'image/png'  => 'png',
        'image/jpeg' => 'jpeg',
        'image/gif'  => 'gif',
    );

    /**
     * Image for the client
     *
     * @var PHPIMS\Image\ImageInterface
     */
    private $image;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->image = new ImageObject();
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
        $this->prepareImage($request, $response);

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

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

        // Identify the image to inject the content type header to the response, and to set the
        // correct mime type and extension in the image instance
        $this->identifyImage($request, $response);

        // Apply transformations
        $transformationChain = $request->getTransformations();

        try {
            $transformationChain->applyToImage($this->image);
        } catch (TransformationException $e) {
            throw new Exception('Transformation failed with message: ' . $e->getMessage(), 401, $e);
        }

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
     * @param PHPIMS\Http\Request\RequestInterface $request The current request
     * @param PHPIMS\Http\Response\ResponseInterface $response The current response
     * @throws PHPIMS\Resource\Exception
     */
    private function prepareImage(RequestInterface $request, ResponseInterface $response) {
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

        $this->identifyImage($request, $response);
    }

    /**
     * Identify the local image property
     *
     * This method will identify the current image using the finfo extension and inject the mime
     * type of the image into the response headers.
     *
     * @param PHPIMS\Http\Request\RequestInterface $request The current request
     * @param PHPIMS\Http\Response\ResponseInterface $response The current response
     * @throws PHPIMS\Resource\Exception
     */
    private function identifyImage(RequestInterface $request, ResponseInterface $response) {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($this->image->getBlob());

        if (!$this->supportedMimeType($mime)) {
            throw new Exception('Unsupported image type: ' . $mime, 415);
        }

        $extension = $this->getFileExtension($mime);

        $this->image->setMimeType($mime)
                    ->setExtension($extension);

        $response->getHeaders()->set('Content-Type', $mime);

        // Update image identifier in case it has a wrong extension
        $imageIdentifier = $request->getImageIdentifier();
        $imageIdentifier = substr($imageIdentifier, 0, 32) . '.' . $extension;
        $request->setImageIdentifier($imageIdentifier);
    }

    /**
     * Check if a mime type is supported by PHPIMS
     *
     * @param string $mime The mime type to check. For instance "image/png"
     * @return boolean
     */
    private function supportedMimeType($mime) {
        return isset(self::$mimeTypes[$mime]);
    }

    /**
     * Get the file extension mapped to a mime type
     *
     * @param string $mime The mime type. For instance "image/png"
     * @return boolean|string The extension (without the leading dot) on success or boolean false
     *                        if the mime type is not supported.
     */
    private function getFileExtension($mime) {
        return isset(self::$mimeTypes[$mime]) ? self::$mimeTypes[$mime] : false;
    }
}
