<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
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
