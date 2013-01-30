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
    Imbo\EventListener\ListenerDefinition,
    Imbo\Http\Request\RequestInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Model;

/**
 * Metadata resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class Metadata implements ResourceInterface, ListenerInterface {
    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('metadata.get', array($this, 'get')),
            new ListenerDefinition('metadata.post', array($this, 'post')),
            new ListenerDefinition('metadata.put', array($this, 'put')),
            new ListenerDefinition('metadata.delete', array($this, 'delete')),
            new ListenerDefinition('metadata.head', array($this, 'get')),
            new ListenerDefinition('metadata.post', array($this, 'validateMetadata'), 10),
            new ListenerDefinition('metadata.put', array($this, 'validateMetadata'), 10),
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

        $lastModified = $response->getLastModified();

        $hash = md5($request->getPublicKey() . $request->getImageIdentifier() . $lastModified);

        $response->getHeaders()->set('ETag', '"' . $hash . '"');
    }

    /**
     * Validate metadata found in the request body
     *
     * @param EventInterface $event The event instance
     * @throws InvalidArgumentException
     */
    public function validateMetadata(EventInterface $event){
        $request = $event->getRequest();
        $metadata = $request->getRawData();

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
