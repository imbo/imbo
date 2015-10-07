<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

class CustomEventListener implements Imbo\EventListener\ListenerInterface {
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
        return [
            'index.get' => 'getIndex',
            'user.get' => 'getUser',
        ];
    }

    /**
     * Set a couple of headers
     *
     * @param Imbo\EventManager\EventInterface $event The current event
     */
    public function getIndex(Imbo\EventManager\EventInterface $event) {
        $event->getResponse()->headers->add([
            'X-Imbo-Value1' => $this->value1,
            'X-Imbo-Value2' => $this->value2,
        ]);
    }

    /**
     * Set a custom header containing the current user
     *
     * @param Imbo\EventManager\EventInterface $event The current event
     */
    public function getUser(Imbo\EventManager\EventInterface $event) {
        $event->getResponse()->headers->set('X-Imbo-CurrentUser', $event->getRequest()->getUser());
    }
}

/**
 * Attach some custom event listeners
 */
return [
    'eventListeners' => [
        'someHandler' => [
            'events' => [
                'index.get' => 1000,
            ],
            'callback' => function(Imbo\EventManager\EventInterface $event) {
                $event->getResponse()->headers->set('X-Imbo-SomeHandler', microtime(true));
            }
        ],
        'someOtherHandler' => [
            'events' => [
                'index.get',
                'index.head',
            ],
            'callback' => function(Imbo\EventManager\EventInterface $event) {
                $event->getResponse()->headers->set('X-Imbo-SomeOtherHandler', microtime(true));
            },
            'priority' => 10,
        ],
        'someEventListener' => [
            'listener' => 'CustomEventListener',
            'params' => ['key1' => 'value1', 'key2' => 'value2'],
            'users' => [
                'whitelist' => ['user'],
            ],
        ],
    ],
];
