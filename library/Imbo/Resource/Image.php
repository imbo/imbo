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
    Imbo\EventListener\ListenerDefinition,
    Imbo\Model;

/**
 * Image resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
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
            new ListenerDefinition('image.head', array($this, 'get')),
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

        $model = new Model\ArrayModel();
        $model->setData(array(
            'imageIdentifier' => $request->getImage()->getChecksum(),
        ));

        $response->setModel($model);
    }

    /**
     * Handle DELETE requests
     *
     * @param EventInterface
     */
    public function delete(EventInterface $event) {
        $event->getManager()->trigger('db.image.delete');
        $event->getManager()->trigger('storage.image.delete');

        $model = new Model\ArrayModel();
        $model->setData(array(
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
        ));

        $event->getResponse()->setModel($model);
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
        $image->setImageIdentifier($imageIdentifier)
              ->setPublicKey($publicKey);

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

        // Fetch the image once more as event listeners might have set a new instance during the
        // transformation phase
        $image = $response->getImage();
        $response->setModel($image);
    }
}
