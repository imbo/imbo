<?php declare(strict_types=1);
use Imbo\Resource;
use MongoDB\BSON\ObjectId as MongoId;

return [
    'accesscontrol' => [
        [
            'publicKey' => 'master-pubkey',
            'privateKey' => 'master-privkey',
            'acl' => [
                [
                    'id' => new MongoId(),
                    'resources' => [
                        Resource::KEY_PUT,
                        Resource::KEY_HEAD,
                        Resource::KEY_DELETE,
                        Resource::KEYS_POST,

                        Resource::ACCESS_RULE_GET,
                        Resource::ACCESS_RULE_HEAD,
                        Resource::ACCESS_RULE_DELETE,

                        Resource::ACCESS_RULES_GET,
                        Resource::ACCESS_RULES_HEAD,
                        Resource::ACCESS_RULES_POST,

                        Resource::GROUP_DELETE,
                    ],
                    'users' => [],
                ],
                [
                    'id' => new MongoId(),
                    'group' => 'something',
                    'users' => ['some-user'],
                ],
            ],
        ],
        [
            'publicKey' => 'foobar',
            'privateKey' => 'barfoo',
            'acl' => [
                [
                    'id' => new MongoId('100000000000000000001337'),
                    'resources' => [
                        Resource::ACCESS_RULE_GET,
                        Resource::ACCESS_RULE_HEAD,
                    ],
                    'users' => ['foobar'],
                ], [
                    'id' => new MongoId('100000000000000000002468'),
                    'resources' => [Resource::IMAGES_GET],
                    'users' => ['barfoo'],
                ],
            ],
        ],
        [
            'publicKey' => 'acl-creator',
            'privateKey' => 'someprivkey',
            'acl' => [[
                'id' => new MongoId(),
                'resources' => [
                    Resource::GROUP_GET,
                    Resource::GROUP_HEAD,
                    Resource::GROUP_PUT,
                    Resource::GROUP_DELETE,
                    Resource::GROUPS_GET,
                    Resource::GROUPS_POST,

                    Resource::ACCESS_RULES_GET,
                    Resource::ACCESS_RULES_HEAD,
                    Resource::ACCESS_RULES_POST,

                    Resource::KEY_HEAD,
                    Resource::KEY_PUT,
                    Resource::KEY_DELETE,
                ],
                'users' => [],
            ]],
        ],
        [
            'publicKey' => 'wildcarded',
            'privateKey' => 'foobar',
            'acl' => [[
                'id' => new MongoId(),
                'group' => 'user-stats',
                'users' => '*',
            ]],
        ],
        [
            'publicKey' => 'group-based',
            'privateKey' => 'foobar',
            'acl' => [[
                'id' => new MongoId('100000000000000000001942'),
                'group' => 'user-stats',
                'users' => ['user1'],
            ]],
        ],

        [
            'publicKey' => 'acl-checker',
            'privateKey' => 'foobar',
            'acl' => [[
                'id' => new MongoId(),
                'resources' => [Resource::ACCESS_RULE_GET],
                'users' => [],
            ]],
        ],
    ],

    'accesscontrolgroup' => [
        [
            'name' => 'existing-group',
            'resources' => [
                Resource::GROUP_GET,
                Resource::GROUP_HEAD,
            ],
        ],
        [
            'name' => 'user-stats',
            'resources' => [
                Resource::USER_GET,
                Resource::USER_HEAD,
            ],
        ],
        [
            'name' => 'something',
            'resources' => [],
        ],
    ],
];
