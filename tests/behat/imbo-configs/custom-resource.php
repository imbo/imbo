<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo;

class CustomResource implements Resource\ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'custom1.get' => 'get',
        );
    }

    /**
     * Send a response
     *
     * @param EventManager\EventInterface $event The current event
     */
    public function get(EventManager\EventInterface $event) {
        $model = new Model\ArrayModel();
        $model->setData(array(
            'event' => $event->getName(),
            'id' => $event->getRequest()->getRoute()->get('id'),
        ));
        $event->getResponse()->setModel($model);
    }
}

/**
 * Attach a couple of custom resources
 */
class CustomResource2 implements Resource\ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'PUT');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'custom2.get' => 'get',
            'custom2.put' => 'put',
        );
    }

    /**
     * Send a response
     *
     * @param EventManager\EventInterface $event The current event
     */
    public function get(EventManager\EventInterface $event) {
        $model = new Model\ArrayModel();
        $model->setData(array(
            'event' => $event->getName(),
        ));
        $event->getResponse()->setModel($model);
    }

    /**
     * Send a response to PUT requests
     *
     * @param EventManager\EventInterface $event The current event
     */
    public function put(EventManager\EventInterface $event) {
        $model = new Model\ArrayModel();
        $model->setData(array(
            'event' => $event->getName(),
        ));
        $event->getResponse()->setModel($model);
    }
}

return array(
    // Add a couple of routes for the two custom resources
    'routes' => array(
        'custom1' => '#^/custom/(?<id>[a-zA-Z0-9]{7})$#',
        'custom2' => '#^/custom(?:\.(?<extension>json|xml))?$#',
    ),

    // Attach the two resources in two different ways
    'resources' => array(
        'custom1' => __NAMESPACE__ . '\CustomResource',
        'custom2' => function() {
            return new CustomResource2();
        }
    ),
);
