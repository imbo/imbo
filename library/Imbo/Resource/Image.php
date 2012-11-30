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
    Imbo\Container,
    Imbo\ContainerAware,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\StorageException,
    Imbo\Exception\ResourceException,
    Imbo\EventManager\EventInterface,
    Imbo\EventManager\EventManager;

/**
 * Image resource
 *
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Image implements ContainerAware, ResourceInterface, ListenerInterface {
    /**
     * @var Container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
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
    public function attach(EventManager $manager) {
        $manager->attach('image.get', array($this, 'get'))
                ->attach('image.head', array($this, 'head'))
                ->attach('image.delete', array($this, 'delete'))
                ->attach('image.put', array($this, 'put'));
    }

    /**
     * Handle PUT requests
     *
     * @param EventInterface
     */
    public function put(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $storage = $event->getStorage();

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getRealImageIdentifier();

        $event->getManager()->trigger('db.image.insert');

        // Store the image
        try {
            $storage->store($publicKey, $imageIdentifier, $response->getImage()->getBlob());
        } catch (StorageException $e) {
            $event->getManager()->trigger('db.image.delete');

            throw $e;
        }

        $response->setStatusCode(201)
                 ->setBody(array('imageIdentifier' => $imageIdentifier));
    }

    /**
     * Handle DELETE requests
     *
     * @param EventInterface
     */
    public function delete(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $storage = $event->getStorage();

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        $event->getManager()->trigger('db.image.delete');
        $storage->delete($publicKey, $imageIdentifier);

        $response->setBody(array(
            'imageIdentifier' => $imageIdentifier,
        ));
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface
     */
    public function get(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $storage = $event->getStorage();

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $serverContainer = $request->getServer();
        $requestHeaders = $request->getHeaders();
        $responseHeaders = $response->getHeaders();
        $image = $response->getImage();

        $event->getManager()->trigger('db.image.load');

        // Generate ETag using public key, image identifier, Accept headers of the user agent and
        // the requested URI
        $etag = '"' . md5(
            $publicKey .
            $imageIdentifier .
            $requestHeaders->get('Accept') .
            $serverContainer->get('REQUEST_URI')
        ) . '"';

        // Fetch formatted last modified timestamp from the storage driver
        $lastModified = $this->container->get('dateFormatter')->formatDate(
            $storage->getLastModified($publicKey, $imageIdentifier)
        );

        // Add the ETag to the response headers
        $responseHeaders->set('ETag', $etag);

        // Fetch the image data and store the data in the image instance
        $imageData = $storage->getImage($publicKey, $imageIdentifier);
        $image->setBlob($imageData);

        // Set some response headers before we apply optional transformations
        $responseHeaders
            // Set the last modification date
            ->set('Last-Modified', $lastModified)

            // Set the max-age to a year since the image never changes
            ->set('Cache-Control', 'max-age=31536000')

            // Custom Imbo headers
            ->set('X-Imbo-OriginalMimeType', $image->getMimeType())
            ->set('X-Imbo-OriginalWidth', $image->getWidth())
            ->set('X-Imbo-OriginalHeight', $image->getHeight())
            ->set('X-Imbo-OriginalFileSize', $image->getFilesize())
            ->set('X-Imbo-OriginalExtension', $image->getExtension());

        $event->getManager()->trigger('image.transform');

        // Set the content length and content-type after transformations have been applied
        $imageData = $image->getBlob();
        $responseHeaders->set('Content-Length', strlen($imageData))
                        ->set('Content-Type', $image->getMimeType());

        $response->setBody($imageData);
    }

    /**
     * Handle HEAD requests
     *
     * @param EventInterface
     */
    public function head(EventInterface $event) {
        $this->get($event);

        // Remove body from the response, but keep everything else
        $event->getResponse()->setBody(null);
    }
}
