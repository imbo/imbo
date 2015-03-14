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
        ]
    ],
    'accesscontrolgroup' => []
];
