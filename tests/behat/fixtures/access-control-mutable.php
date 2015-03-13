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
        ]
    ],
    'accesscontrolgroup' => [

    ]
];
