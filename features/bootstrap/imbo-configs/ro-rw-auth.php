<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter;
use Imbo\Resource;

/**
 * Use individual read-only/read+write keys.
 */
return [
    'accessControl' => static fn (): ArrayAdapter => new ArrayAdapter([
        [
            'publicKey' => 'ro-pubkey',
            'privateKey' => 'read-only-key',
            'acl' => [[
                'resources' => Resource::getReadOnlyResources(),
                'users' => ['someuser'],
            ]],
        ],

        [
            'publicKey' => 'rw-pubkey',
            'privateKey' => 'read+write-key',
            'acl' => [[
                'resources' => Resource::getReadWriteResources(),
                'users' => ['someuser'],
            ]],
        ],

        [
            'publicKey' => 'foo',
            'privateKey' => 'bar',
            'acl' => [[
                'resources' => Resource::getReadOnlyResources(),
                'users' => ['user'],
            ]],
        ],
    ]),
];
