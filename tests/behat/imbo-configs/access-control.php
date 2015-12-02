<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter,
    Imbo\Resource\ResourceInterface,
    Imbo\EventManager\EventInterface,
    Imbo\Model\ListModel,
    Imbo\Resource;

class Foobar implements ResourceInterface {
    public function getAllowedMethods() {
        return ['GET'];
    }

    public static function getSubscribedEvents() {
        return [
            'foobar.get' => 'get',
        ];
    }

    public function get(EventInterface $event) {
        $model = new ListModel();
        $model->setContainer('foo');
        $model->setEntry('bar');
        $model->setList([1, 2, 3]);
        $event->getResponse()->setModel($model);
    }
}

return [
    'accessControl' => function() {
        return new ArrayAdapter([
            [
                'publicKey' => 'valid-pubkey',
                'privateKey' => 'foobar',
                'acl' => [[
                    'users' => ['user1', 'some-user'],
                    'resources' => [
                        'foobar.get',

                        Resource::USER_GET,
                        Resource::KEYS_PUT,
                        Resource::KEYS_HEAD,
                        Resource::KEYS_DELETE,
                        Resource::ACCESS_RULE_GET,
                        Resource::ACCESS_RULE_HEAD,
                        Resource::ACCESS_RULE_DELETE,
                        Resource::ACCESS_RULES_GET,
                        Resource::ACCESS_RULES_HEAD,
                        Resource::ACCESS_RULES_POST
                    ],
                ]]
            ],

            [
                'publicKey' => 'valid-pubkey-with-wildcard',
                'privateKey' => 'foobar',
                'acl' => [[
                    'resources' => [Resource::USER_GET, 'foobar.get'],
                    'users' => '*',
                ]]
            ],

            [
                'publicKey' => 'valid-group-pubkey',
                'privateKey' => 'foobar',
                'acl' => [[
                    'group' => 'images-read',
                    'users' => ['user', 'user2']
                ], [
                    'group' => 'groups-read',
                    'users' => '*'
                ], [
                    'resources' => [Resource::GROUP_DELETE, Resource::GROUP_PUT],
                    'users' => '*'
                ]]
            ],

            [
                'publicKey' => 'acl-checker',
                'privateKey' => 'foobar',
                'acl' => [[
                    'resources' => [Resource::ACCESS_RULE_GET],
                    'users' => [],
                ]]
            ]
        ], [
            'images-read' => [Resource::IMAGES_GET, Resource::IMAGES_HEAD],
            'groups-read' => [
                Resource::GROUP_GET,
                Resource::GROUP_HEAD,
                Resource::GROUPS_GET,
                Resource::GROUPS_HEAD
            ],
        ]);
    },

    'resources' => [
        'foobar' => new Foobar()
    ],
    'routes' => [
        'foobar' => '#^/foobar$#'
    ],
    'eventListeners' => [
        'accessControl' => [
            'listener' => 'Imbo\EventListener\AccessControl',
            'params' => [
                'additionalResources' => ['foobar.get'],
            ],
        ],
    ]
];
