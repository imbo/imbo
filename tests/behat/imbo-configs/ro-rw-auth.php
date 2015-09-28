<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter;

/**
 * Use individual read-only/read+write keys
 */
return [
    'accessControl' => function() {
        return new ArrayAdapter([
            [
                'publicKey'  => 'ro-pubkey',
                'privateKey' => 'read-only-key',
                'acl' => [[
                    'resources' => ArrayAdapter::getReadOnlyResources(),
                    'users' => ['someuser'],
                ]]
            ],

            [
                'publicKey'  => 'rw-pubkey',
                'privateKey' => 'read+write-key',
                'acl' => [[
                    'resources' => ArrayAdapter::getReadWriteResources(),
                    'users' => ['someuser'],
                ]]
            ],

            [
                'publicKey'  => 'foo',
                'privateKey' => 'bar',
                'acl' => [[
                    'resources' => ArrayAdapter::getReadOnlyResources(),
                    'users' => ['user'],
                ]]
            ]
        ]);
    }
];
