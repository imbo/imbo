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
    Imbo\Exception\InvalidArgumentException;

/**
 * Metadata resource
 *
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Metadata extends Resource implements ResourceInterface {
    /**
     * @see Imbo\Resource\ResourceInterface::getAllowedMethods()
     */
    public function getAllowedMethods() {
        return array(
            RequestInterface::METHOD_GET,
            RequestInterface::METHOD_POST,
            RequestInterface::METHOD_PUT,
            RequestInterface::METHOD_DELETE,
            RequestInterface::METHOD_HEAD,
        );
    }

    /**
     * @see Imbo\Resource\ResourceInterface::delete()
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $imageIdentifier = $request->getImageIdentifier();

        $database->deleteMetadata($request->getPublicKey(), $imageIdentifier);

        $this->getResponseWriter()->write(array('imageIdentifier' => $imageIdentifier), $request, $response);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::put()
     */
    public function put(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $metadata = $request->getRawData();

        if (empty($metadata)) {
            throw new InvalidArgumentException('Missing JSON data', 400);
        } else {
            $metadata = json_decode($metadata, true);

            if ($metadata === null) {
                throw new InvalidArgumentException('Invalid JSON data', 400);
            }
        }

        // Remove existing metadata
        $database->deleteMetadata($publicKey, $imageIdentifier);

        // Insert new metadata
        $database->updateMetadata($publicKey, $imageIdentifier, $metadata);

        $this->getResponseWriter()->write(array('imageIdentifier' => $imageIdentifier), $request, $response);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::post()
     */
    public function post(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $imageIdentifier = $request->getImageIdentifier();

        // Fetch metadata from the request
        if ($request->getRequest()->has('metadata')) {
            $metadata = $request->getRequest()->get('metadata');
        } else {
            $metadata = $request->getRawData();
        }

        if (empty($metadata)) {
            throw new InvalidArgumentException('Missing JSON data', 400);
        } else {
            $metadata = json_decode($metadata, true);

            if ($metadata === null) {
                throw new InvalidArgumentException('Invalid JSON data', 400);
            }
        }

        $database->updateMetadata($request->getPublicKey(), $imageIdentifier, $metadata);

        $this->getResponseWriter()->write(array('imageIdentifier' => $imageIdentifier), $request, $response);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $requestHeaders = $request->getHeaders();
        $responseHeaders = $response->getHeaders();

        // See when this particular image was last updated
        $lastModified = $database->getLastModified($publicKey, $imageIdentifier, true);

        // Generate an etag for the content
        $etag = '"' . md5($publicKey . $imageIdentifier . $lastModified) . '"';

        $responseHeaders->set('ETag', $etag);

        if (
            $lastModified === $requestHeaders->get('if-modified-since') &&
            $etag === $requestHeaders->get('if-none-match'))
        {
            // The client already has this object
            $response->setNotModified();
            return;
        }

        // The client did not have this particular version in its cache
        $responseHeaders->set('Last-Modified', $lastModified);

        $metadata = $database->getMetadata($publicKey, $imageIdentifier);

        $this->getResponseWriter()->write($metadata, $request, $response);
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
