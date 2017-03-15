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
use Imbo\Resource;
use Imbo\Database\MongoDB;
use Imbo\Storage\GridFS;

// Default config for testing
$testConfig = [
    'accessControl' => function() {
        return new ArrayAdapter([
            [
                'publicKey' => 'publicKey',
                'privateKey' => 'privateKey',
                'acl' => [[
                    'resources' => Resource::getReadWriteResources(),
                    'users' => ['user', 'other-user'],
                ]]
            ],
            [
                'publicKey' => 'unpriviledged',
                'privateKey' => 'privateKey',
                'acl' => [[
                    'resources' => Resource::getReadWriteResources(),
                    'users' => ['user'],
                ]]
            ],
            [
                'publicKey' => 'wildcard',
                'privateKey' => '*',
                'acl' => [[
                    'resources' => Resource::getReadWriteResources(),
                    'users' => '*'
                ]]
            ]
        ]);
    },

    'database' => function() {
        return new MongoDB([
            'databaseName' => 'imbo_testing',
        ]);
    },

    'storage' => function() {
        return new GridFS([
            'databaseName' => 'imbo_testing',
        ]);
    },
];

// Default Imbo config
$defaultConfig = require __DIR__ . '/../../../config/config.default.php';

// Custom test config, if any, specified in the X-Imbo-Test-Config-File HTTP request header
$customConfig = [];

if (isset($_SERVER['HTTP_X_IMBO_TEST_CONFIG_FILE'])) {
    $customConfig = require __DIR__ . '/' . basename($_SERVER['HTTP_X_IMBO_TEST_CONFIG_FILE']);
}

// Return the merged configuration, having the custom config overwrite the default testing config,
// which in turn overwrites the default config
return array_replace_recursive($defaultConfig, $testConfig, $customConfig);
