<?php declare(strict_types=1);

namespace Imbo;

use Exception as BaseException;

use function defined;
use function is_array;

use const E_USER_ERROR;

$installedByComposer = is_file(__DIR__.'/../../../autoload.php');

if ($installedByComposer) {
    // Imbo has been installed by Composer (composer reqiure imbo/imbo)
    $appDirectory = __DIR__.'/../../../..';
} else {
    // Assume this is a direct install (git clone https://github.com/imbo/imbo)
    $appDirectory = __DIR__.'/..';
}

require $appDirectory.'/vendor/autoload.php';

$defaultConfig = require __DIR__.'/../config/config.default.php';
$extraConfig = [];

if (defined('IMBO_CONFIG_PATH') && is_file(IMBO_CONFIG_PATH)) {
    $extraConfig = require IMBO_CONFIG_PATH;
} else {
    $configLoader = function (string $path): array {
        $config = require $path;

        return is_array($config) ? $config : [];
    };

    $configs = glob($appDirectory.'/config/*.php');
    sort($configs);
    foreach ($configs as $file) {
        if ('config.default.php' === basename($file)) {
            continue;
        }

        $extraConfig = array_replace_recursive(
            $extraConfig,
            $configLoader($file),
        );
    }
}

$config = array_replace_recursive(
    $defaultConfig,
    $extraConfig,
);

$application = new Application($config);

try {
    $application->run();
} catch (BaseException $e) {
    header('HTTP/1.1 500 Internal Server Error');

    if (true === $config['rethrowFinalException']) {
        throw $e;
    }
    trigger_error('Uncaught Exception ('.$e::class.') with message: '.$e->getMessage(), E_USER_ERROR);
}
