<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter;
use Imbo\Resource;

use function assert;

assert(!empty($_SERVER['HTTP_X_BEHAT_DATABASE_ADAPTER']));
assert(!empty($_SERVER['HTTP_X_BEHAT_STORAGE_ADAPTER']));

$databaseAdapter = unserialize(urldecode($_SERVER['HTTP_X_BEHAT_DATABASE_ADAPTER']));
$storageAdapter = unserialize(urldecode($_SERVER['HTTP_X_BEHAT_STORAGE_ADAPTER']));

if (!empty($_SERVER['HTTP_X_BEHAT_BEFORE_SCENARIO'])) {
    $databaseAdapter->setUp();
    $storageAdapter->setUp();
    exit;
}

// Default config for testing
$testConfig = [
    'database' => $databaseAdapter->getAdapter(),
    'storage' => $storageAdapter->getAdapter(),
    'accessControl' => static fn () => new ArrayAdapter([
        [
            'publicKey' => 'publicKey',
            'privateKey' => 'privateKey',
            'acl' => [[
                'resources' => Resource::getReadWriteResources(),
                'users' => ['user', 'other-user'],
            ]],
        ],
        [
            'publicKey' => 'unpriviledged',
            'privateKey' => 'privateKey',
            'acl' => [[
                'resources' => Resource::getReadWriteResources(),
                'users' => ['user'],
            ]],
        ],
        [
            'publicKey' => 'wildcard',
            'privateKey' => '*',
            'acl' => [[
                'resources' => Resource::getReadWriteResources(),
                'users' => '*',
            ]],
        ],
    ]),
];

// Custom test config, if any, specified in the X-Imbo-Test-Config-File HTTP request header
$customConfig = [];

if (isset($_SERVER['HTTP_X_IMBO_TEST_CONFIG_FILE'])) {
    $customConfig = require __DIR__.'/'.basename($_SERVER['HTTP_X_IMBO_TEST_CONFIG_FILE']);
}

// Return the merged configuration, having the custom config overwrite the default testing config,
// which in turn overwrites the default config
return array_replace_recursive(
    require __DIR__.'/../../../config/config.default.php',
    $testConfig,
    $customConfig,
);
