<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Imbo\Auth\AccessControl\Adapter\AdapterInterface as ACI,
    Imbo\Auth\AccessControl\Adapter\ArrayAdapter,
    Imbo\Resource\ResourceInterface,
    Imbo\EventManager\EventInterface,
    Imbo\Model\ListModel;

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
                    'resources' => [ACI::RESOURCE_USER_GET, 'foobar.get'],
                    'users' => ['user1', 'some-user'],
                ]]
            ],

            [
                'publicKey' => 'valid-pubkey-with-wildcard',
                'privateKey' => 'foobar',
                'acl' => [[
                    'resources' => [ACI::RESOURCE_USER_GET, 'foobar.get'],
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
                ]]
            ]
        ], [
            'images-read' => [ACI::RESOURCE_IMAGES_GET],
            'groups-read' => [
                ACI::RESOURCE_GROUPS_GET,
                ACI::RESOURCE_GROUPS_HEAD
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
