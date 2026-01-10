<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;

class CustomEventListener implements ListenerInterface
{
    private $value1;
    private $value2;

    public function __construct(array $params)
    {
        $this->value1 = $params['key1'];
        $this->value2 = $params['key2'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'index.get' => 'getIndex',
            'user.get' => 'getUser',
        ];
    }

    public function getIndex(EventInterface $event)
    {
        $event->getResponse()->headers->add([
            'X-Imbo-Value1' => $this->value1,
            'X-Imbo-Value2' => $this->value2,
        ]);
    }

    public function getUser(EventInterface $event)
    {
        $event->getResponse()->headers->set('X-Imbo-CurrentUser', $event->getRequest()->getUser());
    }
}

return [
    'eventListeners' => [
        'someHandler' => [
            'events' => [
                'index.get' => 1000,
            ],
            'callback' => function (EventInterface $event) {
                $event->getResponse()->headers->set('X-Imbo-SomeHandler', (string) microtime(true));
            },
        ],
        'someOtherHandler' => [
            'events' => [
                'index.get',
                'index.head',
            ],
            'callback' => function (EventInterface $event) {
                $event->getResponse()->headers->set('X-Imbo-SomeOtherHandler', (string) microtime(true));
            },
            'priority' => 10,
        ],
        'someEventListener' => [
            'listener' => CustomEventListener::class,
            'params' => ['key1' => 'value1', 'key2' => 'value2'],
            'users' => [
                'whitelist' => ['user'],
            ],
        ],
    ],
];
