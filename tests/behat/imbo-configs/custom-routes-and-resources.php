<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

class CustomResource implements Imbo\Resource\ResourceInterface {
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
     * @param Imbo\EventManager\EventInterface $event The current event
     */
    public function get(Imbo\EventManager\EventInterface $event) {
        $model = new Imbo\Model\ArrayModel();
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
class CustomResource2 implements Imbo\Resource\ResourceInterface {
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
     * @param Imbo\EventManager\EventInterface $event The current event
     */
    public function get(Imbo\EventManager\EventInterface $event) {
        $model = new Imbo\Model\ArrayModel();
        $model->setData(array(
            'event' => $event->getName(),
        ));
        $event->getResponse()->setModel($model);
    }

    /**
     * Send a response to PUT requests
     *
     * @param Imbo\EventManager\EventInterface $event The current event
     */
    public function put(Imbo\EventManager\EventInterface $event) {
        $model = new Imbo\Model\ArrayModel();
        $model->setData(array(
            'event' => $event->getName(),
        ));
        $event->getResponse()->setModel($model);
    }
}

/**
 * Add a couple of resources and routes
 */
return array(
    'routes' => array(
        'custom1' => '#^/custom/(?<id>[a-zA-Z0-9]{7})$#',
        'custom2' => '#^/custom(?:\.(?<extension>json|xml))?$#',
    ),
    'resources' => array(
        'custom1' => 'CustomResource',
        'custom2' => function() {
            return new CustomResource2();
        }
    ),
);
