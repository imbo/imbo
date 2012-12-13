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
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\ResourceException,
    Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerDefinition;

/**
 * Image resource
 *
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Image implements ResourceInterface, ListenerInterface {
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
    public function getDefinition() {
        return array(
            new ListenerDefinition('image.get', array($this, 'get')),
            new ListenerDefinition('image.head', array($this, 'head')),
            new ListenerDefinition('image.delete', array($this, 'delete')),
            new ListenerDefinition('image.put', array($this, 'put')),
        );
    }

    /**
     * Handle PUT requests
     *
     * @param EventInterface
     */
    public function put(EventInterface $event) {
        $event->getManager()->trigger('db.image.insert');
        $event->getManager()->trigger('storage.image.insert');

        $request = $event->getRequest();
        $response = $event->getResponse();

        $response->setBody(array('imageIdentifier' => $request->getImage()->getChecksum()));
    }

    /**
     * Handle DELETE requests
     *
     * @param EventInterface
     */
    public function delete(EventInterface $event) {
        $event->getManager()->trigger('db.image.delete');
        $event->getManager()->trigger('storage.image.delete');
        $event->getResponse()->setBody(array(
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
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

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $serverContainer = $request->getServer();
        $requestHeaders = $request->getHeaders();
        $responseHeaders = $response->getHeaders();
        $image = $response->getImage();

        $event->getManager()->trigger('db.image.load');
        $event->getManager()->trigger('storage.image.load');

        // Generate ETag using public key, image identifier, Accept headers of the user agent and
        // the requested URI
        $etag = '"' . md5(
            $publicKey .
            $imageIdentifier .
            $requestHeaders->get('Accept') .
            $serverContainer->get('REQUEST_URI')
        ) . '"';

        // Set some response headers before we apply optional transformations
        $responseHeaders
            // ETags
            ->set('ETag', $etag)

            // Set the max-age to a year since the image never changes
            ->set('Cache-Control', 'max-age=31536000')

            // Custom Imbo headers
            ->set('X-Imbo-OriginalMimeType', $image->getMimeType())
            ->set('X-Imbo-OriginalWidth', $image->getWidth())
            ->set('X-Imbo-OriginalHeight', $image->getHeight())
            ->set('X-Imbo-OriginalFileSize', $image->getFilesize())
            ->set('X-Imbo-OriginalExtension', $image->getExtension());

        // Trigger possible image transformations
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
