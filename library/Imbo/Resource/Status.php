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
    Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerDefinition,
    Imbo\EventListener\ListenerInterface,
    Imbo\Container,
    Imbo\ContainerAware,
    DateTime;

/**
 * Status resource
 *
 * This resource can be used to monitor the imbo installation to see if it has access to the
 * current database and storage.
 *
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class Status implements ContainerAware, ResourceInterface, ListenerInterface {
    /**
     * Service container
     *
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
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('status.get', array($this, 'get')),
            new ListenerDefinition('status.head', array($this, 'head')),
        );
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $response = $event->getResponse();
        $database = $event->getDatabase();
        $storage = $event->getStorage();

        $response->getHeaders()->set('Cache-Control', 'max-age=0');

        $databaseStatus = $database->getStatus();
        $storageStatus = $storage->getStatus();

        if (!$databaseStatus || !$storageStatus) {
            if (!$databaseStatus && !$storageStatus) {
                $message = 'Database and storage error';
            } else if (!$storageStatus) {
                $message = 'Storage error';
            } else {
                $message = 'Database error';
            }

            $response->setStatusCode(500)
                     ->setStatusMessage($message);
        }

        $response->setBody(array(
            'date'     => $this->container->get('dateFormatter')->formatDate(new DateTime()),
            'database' => $databaseStatus,
            'storage'  => $storageStatus,
        ));
    }

    /**
     * Handle HEAD requests
     *
     * @param EventInterface $event The current event
     */
    public function head(EventInterface $event) {
        $this->get($event);

        // Remove body from the response, but keep everything else
        $event->getResponse()->setBody(null);
    }
}
