<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter;
use Imbo\Resource;
use InvalidArgumentException;
use RuntimeException;

if (empty($_SERVER['HTTP_X_BEHAT_DATABASE_TEST'])) {
    throw new RuntimeException('Missing X-Behat-Database-Test request header');
} elseif (empty($_SERVER['HTTP_X_BEHAT_STORAGE_TEST'])) {
    throw new RuntimeException('Missing X-Behat-Storage-Test request header');
} elseif (empty($_SERVER['HTTP_X_BEHAT_DATABASE_TEST_CONFIG'])) {
    throw new RuntimeException('Missing X-Behat-Database-Test-Config request header');
} elseif (empty($_SERVER['HTTP_X_BEHAT_STORAGE_TEST_CONFIG'])) {
    throw new RuntimeException('Missing X-Behat-Storage-Test-Config request header');
}

$databaseTest = $_SERVER['HTTP_X_BEHAT_DATABASE_TEST'];
$storageTest = $_SERVER['HTTP_X_BEHAT_STORAGE_TEST'];
$databaseConfig = json_decode(urldecode($_SERVER['HTTP_X_BEHAT_DATABASE_TEST_CONFIG']), true);
$storageConfig = json_decode(urldecode($_SERVER['HTTP_X_BEHAT_STORAGE_TEST_CONFIG']), true);

if (!is_array($databaseConfig)) {
    throw new InvalidArgumentException('Invalid value for X-Behat-Database-Test-Config request header');
} elseif (!is_array($storageConfig)) {
    throw new InvalidArgumentException('Invalid value for X-Behat-Storage-Test-Config request header');
}

// Default config for testing
$testConfig = [
    'accessControl' => function () {
        return new ArrayAdapter([
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
        ]);
    },

    'database' => $databaseTest::getAdapter($databaseConfig),
    'storage' => $storageTest::getAdapter($storageConfig),
];

// Custom test config, if any, specified in the X-Imbo-Test-Config-File HTTP request header
$customConfig = [];

if (isset($_SERVER['HTTP_X_IMBO_TEST_CONFIG_FILE'])) {
    $customConfig = require __DIR__ . '/' . basename($_SERVER['HTTP_X_IMBO_TEST_CONFIG_FILE']);
}

// Return the merged configuration, having the custom config overwrite the default testing config,
// which in turn overwrites the default config
return array_replace_recursive(
    require __DIR__ . '/../../../config/config.default.php',
    $testConfig,
    $customConfig,
);
