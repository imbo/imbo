<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter;
use Imbo\EventListener\AccessControl;
use Imbo\EventManager\EventInterface;
use Imbo\Model\ListModel;
use Imbo\Resource;
use Imbo\Resource\ResourceInterface;

class Foobar implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'foobar.get' => 'get',
        ];
    }

    public function get(EventInterface $event): void
    {
        $event->getResponse()->setModel(new ListModel('foo', [1, 2, 3]));
    }
}

return [
    'accessControl' => new ArrayAdapter([
        [
            'publicKey' => 'valid-pubkey',
            'privateKey' => 'foobar',
            'acl' => [[
                'users' => ['user1', 'some-user'],
                'resources' => [
                    'foobar.get',

                    Resource::USER_GET,
                    Resource::KEYS_POST,
                    Resource::KEY_PUT,
                    Resource::KEY_HEAD,
                    Resource::KEY_DELETE,
                    Resource::ACCESS_RULE_GET,
                    Resource::ACCESS_RULE_HEAD,
                    Resource::ACCESS_RULE_DELETE,
                    Resource::ACCESS_RULES_GET,
                    Resource::ACCESS_RULES_HEAD,
                    Resource::ACCESS_RULES_POST,
                ],
            ]],
        ],

        [
            'publicKey' => 'valid-pubkey-with-wildcard',
            'privateKey' => 'foobar',
            'acl' => [[
                'resources' => [
                    Resource::USER_GET,
                    'foobar.get',
                ],
                'users' => '*',
            ]],
        ],

        [
            'publicKey' => 'valid-group-pubkey',
            'privateKey' => 'foobar',
            'acl' => [[
                'group' => 'images-read',
                'users' => [
                    'user',
                    'user2',
                ],
            ], [
                'group' => 'groups-read',
                'users' => '*',
            ], [
                'resources' => [
                    Resource::GROUP_DELETE,
                    Resource::GROUP_PUT,
                ],
                'users' => '*',
            ]],
        ],

        [
            'publicKey' => 'acl-checker',
            'privateKey' => 'foobar',
            'acl' => [[
                'resources' => [Resource::ACCESS_RULE_GET],
                'users' => [],
            ]],
        ],
    ], [
        'images-read' => [
            Resource::IMAGES_GET,
            Resource::IMAGES_HEAD,
        ],
        'groups-read' => [
            Resource::GROUP_GET,
            Resource::GROUP_HEAD,
            Resource::GROUPS_GET,
            Resource::GROUPS_HEAD,
        ],
    ]),

    'resources' => [
        'foobar' => new Foobar(),
    ],
    'routes' => [
        'foobar' => '#^/foobar$#',
    ],
    'eventListeners' => [
        'accessControl' => [
            'listener' => AccessControl::class,
            'params' => [
                'additionalResources' => ['foobar.get'],
            ],
        ],
    ],
];
