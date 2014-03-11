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

class CustomEventListener implements EventListener\ListenerInterface {
    /**
     * @var string
     */
    private $value1;

    /**
     * @var string
     */
    private $value2;

    /**
     * Class constructor
     *
     * @param array $params
     */
    public function __construct(array $params) {
        $this->value1 = $params['key1'];
        $this->value2 = $params['key2'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'index.get' => 'getIndex',
            'user.get' => 'getUser',
        );
    }

    /**
     * Set a couple of headers
     *
     * @param EventManager\EventInterface $event The current event
     */
    public function getIndex(EventManager\EventInterface $event) {
        $event->getResponse()->headers->add(array(
            'X-Imbo-Value1' => $this->value1,
            'X-Imbo-Value2' => $this->value2,
        ));
    }

    /**
     * Set a custom header containing the current public key
     *
     * @param EventManager\EventInterface $event The current event
     */
    public function getUser(EventManager\EventInterface $event) {
        $event->getResponse()->headers->set('X-Imbo-CurrentUser', $event->getRequest()->getPublicKey());
    }
}

/**
 * Attach some custom event listeners
 */
return array(
    'eventListeners' => array(
        'someHandler' => array(
            'events' => array(
                'index.get' => 1000,
            ),
            'callback' => function(EventManager\EventInterface $event) {
                $event->getResponse()->headers->set('X-Imbo-SomeHandler', microtime(true));
            }
        ),
        'someOtherHandler' => array(
            'events' => array(
                'index.get',
                'index.head',
            ),
            'callback' => function(EventManager\EventInterface $event) {
                $event->getResponse()->headers->set('X-Imbo-SomeOtherHandler', microtime(true));
            },
            'priority' => 10,
        ),
        'someEventListener' => array(
            'listener' => __NAMESPACE__ . '\CustomEventListener',
            'params' => array('key1' => 'value1', 'key2' => 'value2'),
            'publicKeys' => array(
                'whitelist' => array('publickey'),
            ),
        ),
    ),
);
