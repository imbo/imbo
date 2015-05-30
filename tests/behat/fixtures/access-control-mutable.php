<?php
use Imbo\Auth\AccessControl\Adapter\AdapterInterface as ACI;

return [
    'accesscontrol' => [
        [
            'publicKey' => 'master-pubkey',
            'privateKey' => 'master-privkey',
            'acl' => [
                [
                    'id' => new MongoId(),
                    'resources' => [
                        ACI::RESOURCE_KEYS_PUT,
                        ACI::RESOURCE_KEYS_DELETE,

                        ACI::RESOURCE_ACCESS_RULE_GET,
                        ACI::RESOURCE_ACCESS_RULE_HEAD,
                        ACI::RESOURCE_ACCESS_RULE_DELETE,

                        ACI::RESOURCE_ACCESS_RULES_GET,
                        ACI::RESOURCE_ACCESS_RULES_HEAD,
                        ACI::RESOURCE_ACCESS_RULES_POST,
                    ],
                    'users' => []
                ],
                [
                    'id' => new MongoId(),
                    'group' => 'something',
                    'users' => ['some-user']
                ]
            ]
        ],
        [
            'publicKey' => 'foobar',
            'privateKey' => 'barfoo',
            'acl' => [
                [
                    'id' => new MongoId('100000000000000000001337'),
                    'resources' => ['access.get', 'access.head'],
                    'users' => []
                ]
            ]
        ],
        [
            'publicKey' => 'acl-creator',
            'privateKey' => 'someprivkey',
            'acl' => [
                [
                    'id' => new MongoId(),
                    'resources' => [
                        'group.get', 'group.head', 'group.put', 'group.delete',
                        'accessrules.get', 'accessrules.head', 'accessrules.post',
                        'keys.put', 'keys.delete',
                    ],
                    'users' => [],
                ]
            ]
        ],
        [
            'publicKey' => 'wildcarded',
            'privateKey' => 'foobar',
            'acl' => [
                [
                    'id' => new MongoId(),
                    'group' => 'user-stats',
                    'users' => '*'
                ]
            ]
        ],
        [
            'publicKey' => 'group-based',
            'privateKey' => 'foobar',
            'acl' => [
                [
                    'id' => new MongoId(),
                    'group' => 'user-stats',
                    'users' => ['user1']
                ]
            ]
        ],
    ],

    'accesscontrolgroup' => [
        [
            'name' => 'existing-group',
            'resources' => ['group.get', 'group.head'],
        ],
        [
            'name' => 'user-stats',
            'resources' => ['user.get', 'user.head'],
        ],
        [
            'name' => 'something',
            'resources' => []
        ]
    ],
];
