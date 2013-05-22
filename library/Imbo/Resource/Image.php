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

use Imbo\EventListener\ListenerInterface,
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
        return array('GET', 'HEAD', 'DELETE', 'PUT');
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
        $image = $request->getImage();

        $model = new Model\ArrayModel();
        $model->setData(array(
            'imageIdentifier' => $image->getChecksum(),
            'width' => $image->getWidth(),
            'height' => $image->getHeight(),
            'extension' => $image->getExtension(),
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
        $eventManager = $event->getManager();

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        $image = $response->getImage();
        $image->setImageIdentifier($imageIdentifier)
              ->setPublicKey($publicKey);

        $eventManager->trigger('db.image.load');
        $eventManager->trigger('storage.image.load');

        // Generate ETag using public key, image identifier, Accept headers of the user agent and
        // the requested URI
        $etag = '"' . md5(
            $publicKey .
            $imageIdentifier .
            $request->headers->get('Accept', '*/*') .
            $request->getRequestUri()
        ) . '"';

        // Set some response headers before we apply optional transformations
        $response->setEtag($etag)
                 ->setMaxAge(31536000);

        // Custom Imbo headers
        $response->headers->add(array(
            'X-Imbo-OriginalMimeType' => $image->getMimeType(),
            'X-Imbo-OriginalWidth' => $image->getWidth(),
            'X-Imbo-OriginalHeight' => $image->getHeight(),
            'X-Imbo-OriginalFileSize' => $image->getFilesize(),
            'X-Imbo-OriginalExtension' => $image->getExtension(),
        ));

        // Trigger possible image transformations
        $eventManager->trigger('image.transform');

        // Fetch the image once more as event listeners might have set a new instance during the
        // transformation phase
        $response->setModel($response->getImage());
    }
}
