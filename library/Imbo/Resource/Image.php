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
    Imbo\Image\Image as ImageObject,
    Imbo\Exception\StorageException,
    Imbo\Exception\ResourceException,
    Imbo\Image\Transformation\TransformationInterface,
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
     * An array of registered transformation handlers
     *
     * @var array
     */
    private $transformationHandlers = array();

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

        // Fetch and apply transformations
        foreach ($request->getTransformations() as $transformation) {
            $name = $transformation['name'];

            if (!isset($this->transformationHandlers[$name])) {
                throw new ResourceException('Unknown transformation: ' . $name, 400);
            }

            $callback = $this->transformationHandlers[$name];
            $transformation = $callback($transformation['params']);

            if ($transformation instanceof TransformationInterface) {
                $transformation->applyToImage($image);
            } else if (is_callable($transformation)) {
                $transformation($image);
            }
        }

        // See if we want to trigger a conversion. This happens if the user agent has specified an
        // image type in the URI, or if the user agent does not accept the original content type of
        // the requested image.
        $extension = $request->getExtension();
        $imageType = $image->getMimeType();
        $acceptableTypes = $request->getAcceptableContentTypes();

        if (!$extension && !$this->contentNegotiation->isAcceptable($imageType, $acceptableTypes)) {
            $typesToCheck = ImageObject::$mimeTypes;

            $match = $this->container->get('contentNegotiation')->bestMatch(
                array_keys($typesToCheck),
                $acceptableTypes
            );

            if (!$match) {
                throw new ResourceException('Not Acceptable', 406);
            }

            if ($match !== $imageType) {
                // The match is of a different type than the original image
                $extension = $typesToCheck[$match];
            }
        }

        if ($extension) {
            // Trigger a conversion
            $callback = $this->transformationHandlers['convert'];

            $convert = $callback(array('type' => $extension));
            $convert->applyToImage($image);
        }

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

    /**
     * Register an image transformation handler
     *
     * @param string $name The name of the transformation, as used in the query parameters
     * @param callable $callback A piece of code that can be executed. The callback will receive a
     *                           single parameter: $params, which is an array with parameters
     *                           associated with the transformation. The callable must return an
     *                           instance of Imbo\Image\Transformation\TransformationInterface
     * @return ResourceInterface
     */
    public function registerTransformationHandler($name, $callback) {
        $this->transformationHandlers[$name] = $callback;

        return $this;
    }
}
