<?php

return [
    'accesscontrol' => [
        [
            'publicKey' => 'master-pubkey',
            'privateKey' => 'master-privkey',
            'acl' => [
                [
                    'id' => new MongoId(),
                    'resources' => ['access.get', 'access.head'],
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
        ]
    ],

    'accesscontrolgroup' => [
        [
            'name' => 'existing-group',
            'resources' => ['group.get', 'group.head'],
        ]
    ],
];
