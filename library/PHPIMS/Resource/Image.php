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
use PHPIMS\Image\ImageInterface;
use PHPIMS\Resource\ResourceInterface;
use PHPIMS\Resource\Plugin;
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
     * Class constructor
     *
     * @param PHPIMS\Image\ImageInterface $image Image instance used by this resource and its
     *                                           plugins
     */
    public function __construct(ImageInterface $image) {
        $this->image = $image;

        $prepareImage    = new Plugin\PrepareImage($this->image);
        $identifyImage   = new Plugin\IdentifyImage($this->image);

        $this->registerPlugin(ResourceInterface::STATE_PRE,  RequestInterface::METHOD_PUT,    101, $prepareImage)
             ->registerPlugin(ResourceInterface::STATE_PRE,  RequestInterface::METHOD_PUT,    102, $identifyImage)
             ->registerPlugin(ResourceInterface::STATE_POST, RequestInterface::METHOD_GET,    100, $identifyImage);
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
}
