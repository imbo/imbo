<?php
use Imbo\Resource;

return [
    'accesscontrol' => [
        [
            'publicKey' => 'master-pubkey',
            'privateKey' => 'master-privkey',
            'acl' => [
                [
                    'id' => new MongoId(),
                    'resources' => [
                        Resource::KEYS_PUT,
                        Resource::KEYS_HEAD,
                        Resource::KEYS_DELETE,

                        Resource::ACCESS_RULE_GET,
                        Resource::ACCESS_RULE_HEAD,
                        Resource::ACCESS_RULE_DELETE,

                        Resource::ACCESS_RULES_GET,
                        Resource::ACCESS_RULES_HEAD,
                        Resource::ACCESS_RULES_POST,
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
                    'users' => ['foobar']
                ], [
                    'id' => new MongoId('100000000000000000002468'),
                    'resources' => ['images.get'],
                    'users' => ['barfoo']
                ]
            ]
        ],
        [
            'publicKey' => 'acl-creator',
            'privateKey' => 'someprivkey',
            'acl' => [[
                'id' => new MongoId(),
                'resources' => [
                    'group.get', 'group.head', 'group.put', 'group.delete',
                    'accessrules.get', 'accessrules.head', 'accessrules.post',
                    'keys.head', 'keys.put', 'keys.delete', 'groups.get'
                ],
                'users' => [],
            ]]
        ],
        [
            'publicKey' => 'wildcarded',
            'privateKey' => 'foobar',
            'acl' => [[
                'id' => new MongoId(),
                'group' => 'user-stats',
                'users' => '*'
            ]]
        ],
        [
            'publicKey' => 'group-based',
            'privateKey' => 'foobar',
            'acl' => [[
                'id' => new MongoId('100000000000000000001942'),
                'group' => 'user-stats',
                'users' => ['user1']
            ]]
        ],

        [
            'publicKey' => 'acl-checker',
            'privateKey' => 'foobar',
            'acl' => [[
                'id' => new MongoId(),
                'resources' => [Resource::ACCESS_RULE_GET],
                'users' => [],
            ]]
        ]
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
