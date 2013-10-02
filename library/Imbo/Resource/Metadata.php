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

use Imbo\EventManager\EventInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Model;

/**
 * Metadata resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class Metadata implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'POST', 'PUT', 'DELETE', 'HEAD');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'metadata.head' => 'get',
            'metadata.get' => 'get',
            'metadata.post' => array(
                'post',
                'validateMetadata' => 10,
            ),
            'metadata.put' => array(
                'put',
                'validateMetadata' => 10,
            ),
            'metadata.delete' => 'delete',
        );
    }

    /**
     * Handle DELETE requests
     *
     * @param EventInterface $event The current event
     */
    public function delete(EventInterface $event) {
        $event->getManager()->trigger('db.metadata.delete');

        $model = new Model\ArrayModel();
        $model->setData(array(
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
        ));

        $event->getResponse()->setModel($model);
    }

    /**
     * Handle PUT requests
     *
     * @param EventInterface $event The current event
     */
    public function put(EventInterface $event) {
        $event->getManager()->trigger('db.metadata.delete')
                            ->trigger('db.metadata.update');

        $model = new Model\ArrayModel();
        $model->setData(array(
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
        ));

        $event->getResponse()->setModel($model);
    }

    /**
     * Handle POST requests
     *
     * @param EventInterface $event The current event
     */
    public function post(EventInterface $event) {
        $event->getManager()->trigger('db.metadata.update');

        $model = new Model\ArrayModel();
        $model->setData(array(
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
        ));

        $event->getResponse()->setModel($model);
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $event->getManager()->trigger('db.metadata.load');

        $lastModified = $response->getLastModified()->format('D, d M Y H:i:s') . ' GMT';

        $hash = md5($request->getPublicKey() . $request->getImageIdentifier() . $lastModified);

        $response->setEtag('"' . $hash . '"');
    }

    /**
     * Validate metadata found in the request body
     *
     * @param EventInterface $event The event instance
     * @throws InvalidArgumentException
     */
    public function validateMetadata(EventInterface $event) {
        $request = $event->getRequest();
        $metadata = $request->getContent();

        if (empty($metadata)) {
            throw new InvalidArgumentException('Missing JSON data', 400);
        } else {
            $metadata = json_decode($metadata, true);

            if ($metadata === null) {
                throw new InvalidArgumentException('Invalid JSON data', 400);
            }
        }
    }
}
